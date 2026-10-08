<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\ThemeQuestion;
use App\Models\User;
use App\Services\Game\GameEngine;
use App\Services\Game\ReelService;
use Database\Factories\QuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class GamePointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_game_starts_with_100_points_and_exposes_spin_cost(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/game/start', ['level' => 1]);

        $response->assertOk()->assertJsonPath('current_score', 100)->assertJsonPath('spin_cost', 10);
        $this->assertDatabaseHas('game_sessions', ['id' => $response->json('session_id'), 'current_score' => 100]);
        $this->assertDatabaseHas('score_events', [
            'session_id' => $response->json('session_id'),
            'event_type' => 'starting_points',
            'points_delta' => 100,
            'cumulative_score' => 100,
        ]);
    }

    #[TestWith([100, 90])]
    #[TestWith([10, 0])]
    public function test_spin_deducts_ten_points_and_records_the_cost(int $balance, int $remaining): void
    {
        $session = $this->startGameSession();
        $session->update(['current_score' => $balance]);

        $response = $this->postJson("/game/{$session->id}/spin");

        $response->assertOk()->assertJsonPath('current_score', $remaining)->assertJsonCount(3, 'reels');
        $this->assertDatabaseHas('game_sessions', ['id' => $session->id, 'current_score' => $remaining]);
        $this->assertDatabaseHas('score_events', [
            'session_id' => $session->id,
            'event_type' => 'spin_cost',
            'points_delta' => -10,
            'cumulative_score' => $remaining,
        ]);
    }

    #[TestWith([0])]
    #[TestWith([9])]
    public function test_insufficient_points_returns_422_without_changing_reels_or_score(int $balance): void
    {
        $session = $this->startGameSession();
        $session->update(['current_score' => $balance, 'current_reel_result' => [1, 2, 3]]);

        $this->postJson("/game/{$session->id}/spin")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Niet genoeg punten. Een spin kost 10 punten.');

        $this->assertSame($balance, $session->fresh()->current_score);
        $this->assertSame([1, 2, 3], $session->fresh()->current_reel_result);
        $this->assertDatabaseMissing('score_events', ['event_type' => 'spin_cost']);
    }

    #[TestWith(['current_question_id', 1])]
    #[TestWith(['current_theme_question_id', 1])]
    #[TestWith(['led_krans_pending', true])]
    #[TestWith(['is_won', true])]
    public function test_blocked_spin_returns_422_without_charging(string $attribute, mixed $value): void
    {
        $session = $this->startGameSession();
        $session->update([$attribute => $value]);

        $this->postJson("/game/{$session->id}/spin")->assertUnprocessable();

        $this->assertDatabaseHas('game_sessions', ['id' => $session->id, 'current_score' => 100]);
        $this->assertDatabaseMissing('score_events', ['event_type' => 'spin_cost']);
    }

    public function test_reel_cannot_be_held_again_after_a_wrong_answer_until_the_next_spin(): void
    {
        $session = $this->startGameSession();
        QuestionFactory::new()->count(2)->create();
        $session->update(['current_reel_result' => [7, 2, 3]]);
        $questionId = $this->postJson("/game/{$session->id}/hold", ['reel' => 1])->assertOk()->json('question.id');
        $this->postJson("/game/{$session->id}/answer", ['question_id' => $questionId, 'answer' => 'b'])
            ->assertOk()->assertJsonPath('correct', false)->assertJsonPath('state.used_reels', [1]);

        $this->postJson("/game/{$session->id}/hold", ['reel' => 1])->assertUnprocessable();

        $this->postJson("/game/{$session->id}/spin")->assertOk()->assertJsonPath('used_reels', []);
    }

    public function test_spin_with_all_reels_held_releases_them(): void
    {
        $session = $this->startGameSession();
        $session->update(['held_reels' => [1, 2, 3], 'current_reel_result' => [7, 7, 7]]);

        $this->postJson("/game/{$session->id}/spin")->assertOk()->assertJsonPath('held_reels', []);

        $this->assertDatabaseHas('game_sessions', ['id' => $session->id, 'current_score' => 90]);
    }

    public function test_failed_spin_rolls_back_the_cost(): void
    {
        $session = $this->startGameSession();
        $this->mock(ReelService::class)->shouldReceive('spin')->once()->andThrow(new RuntimeException('Spin failed.'));

        $this->postJson("/game/{$session->id}/spin")->assertUnprocessable();

        $this->assertDatabaseHas('game_sessions', ['id' => $session->id, 'current_score' => 100]);
        $this->assertDatabaseMissing('score_events', ['event_type' => 'spin_cost']);
    }

    #[TestWith([1, [7, 2, 3], 10])]
    #[TestWith([2, [7, 2, 3], 20])]
    #[TestWith([3, [7, 2, 3], 30])]
    #[TestWith([1, [7, 7, 3], 40])]
    #[TestWith([1, [7, 7, 7], 50])]
    public function test_correct_answer_adds_the_shown_reward_once_even_with_zero_balance(int $level, array $reels, int $reward): void
    {
        $session = $this->startGameSession($level);
        $question = QuestionFactory::new()->create(['difficulty' => $level]);
        $session->update(['current_score' => 0, 'current_reel_result' => $reels]);
        $this->postJson("/game/{$session->id}/hold", ['reel' => 1])->assertOk()->assertJsonPath('points', $reward);

        $this->postJson("/game/{$session->id}/answer", ['question_id' => $question->id, 'answer' => 'a'])
            ->assertOk()->assertJsonPath('correct', true)->assertJsonPath('points_awarded', $reward)
            ->assertJsonPath('state.current_score', $reward);

        $this->assertDatabaseHas('score_events', [
            'session_id' => $session->id,
            'event_type' => 'question_correct',
            'points_delta' => $reward,
            'cumulative_score' => $reward,
            'question_id' => $question->id,
        ]);

        $this->postJson("/game/{$session->id}/answer", ['question_id' => $question->id, 'answer' => 'a'])
            ->assertUnprocessable();
        $this->assertDatabaseHas('game_sessions', ['id' => $session->id, 'current_score' => $reward]);
        $this->assertSame(1, $session->scoreEvents()->where('event_type', 'question_correct')->count());
    }

    public function test_wrong_answer_does_not_award_points(): void
    {
        $session = $this->startGameSession();
        $question = QuestionFactory::new()->create();
        $session->update(['current_reel_result' => [7, 2, 3]]);
        $this->postJson("/game/{$session->id}/hold", ['reel' => 1])->assertOk();

        $this->postJson("/game/{$session->id}/answer", ['question_id' => $question->id, 'answer' => 'b'])
            ->assertOk()->assertJsonPath('correct', false)->assertJsonPath('points_awarded', 0)
            ->assertJsonPath('state.current_score', 100);

        $this->assertDatabaseHas('game_sessions', ['id' => $session->id, 'current_score' => 100]);
        $this->assertDatabaseMissing('score_events', ['event_type' => 'question_correct']);
    }

    public function test_another_player_cannot_spend_session_points(): void
    {
        $session = $this->startGameSession();

        $this->actingAs(User::factory()->create())->postJson("/game/{$session->id}/spin")->assertForbidden();

        $this->assertDatabaseHas('game_sessions', ['id' => $session->id, 'current_score' => 100]);
        $this->assertDatabaseMissing('score_events', ['event_type' => 'spin_cost']);
    }

    public function test_game_page_shows_the_point_rules(): void
    {
        $this->actingAs(User::factory()->create())->get('/game')
            ->assertOk()->assertSee('100 punten')->assertSee('10 punten')->assertSee('JOUW PUNTEN');
    }

    #[TestWith([true, 1])]
    #[TestWith([false, 0])]
    public function test_completing_a_row_spins_a_theme_question_and_resets_the_row(bool $answerRight, int $checks): void
    {
        $session = $this->startGameSession();
        $themeQuestion = ThemeQuestion::create([
            'theme_id' => 1, 'theme_name' => 'Duurzaamheid', 'question_type' => 'mc_1goed',
            'question_text' => 'Groen?', 'difficulty' => 1,
            'answer_options' => [['key' => 'a', 'text' => 'Ja'], ['key' => 'b', 'text' => 'Nee']],
            'correct_answer' => 'a', 'feedback_correct' => 'Goed', 'feedback_wrong' => 'Fout',
        ]);
        $session->update([
            'theme_ict_checks' => 3, 'theme_inclusie_checks' => 3, 'theme_wereldburger_checks' => 3,
            'current_reel_result' => [7, 2, 3],
        ]);
        $grid = $session->badgeboardState->icon_states;
        $grid[2] = [1 => 1, 2 => 1, 3 => null, 4 => 0, 5 => 1]; // only Nederland (category 7, col 4) missing
        $session->badgeboardState->update(['icon_states' => $grid]);
        $question = QuestionFactory::new()->create();
        $this->postJson("/game/{$session->id}/hold", ['reel' => 1])->assertOk();

        $this->postJson("/game/{$session->id}/answer", ['question_id' => $question->id, 'answer' => 'a'])
            ->assertOk()
            ->assertJsonPath('horizontal_bonus.row', 2)
            ->assertJsonPath('horizontal_bonus.theme_id', 1)
            ->assertJsonPath('horizontal_bonus.slot', 1)
            ->assertJsonPath('horizontal_bonus.question.id', $themeQuestion->id)
            ->assertJsonPath('state.badgeboard.icon_states.2', [1 => 1, 2 => 1, 3 => null, 4 => 1, 5 => 1]);

        $this->postJson("/game/{$session->id}/theme-answer", ['question_id' => $themeQuestion->id, 'answer' => $answerRight ? 'a' : 'b'])
            ->assertOk()
            ->assertJsonPath('correct', $answerRight)
            ->assertJsonPath('state.themes.1.checks', $checks)
            ->assertJsonPath('state.themes.1.active', $answerRight)
            ->assertJsonPath('state.badgeboard.icon_states.2', [1 => 0, 2 => 0, 3 => null, 4 => 0, 5 => 0])
            ->assertJsonPath('state.badgeboard.horizontals.2', false);
    }

    private function startGameSession(int $level = 1): GameSession
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return app(GameEngine::class)->start($user, $level);
    }
}
