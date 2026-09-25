<?php

namespace Tests\Feature;

use App\Models\GameSession;
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
    #[TestWith(['held_reels', [1, 2, 3]])]
    public function test_blocked_spin_returns_422_without_charging(string $attribute, mixed $value): void
    {
        $session = $this->startGameSession();
        $session->update([$attribute => $value]);

        $this->postJson("/game/{$session->id}/spin")->assertUnprocessable();

        $this->assertDatabaseHas('game_sessions', ['id' => $session->id, 'current_score' => 100]);
        $this->assertDatabaseMissing('score_events', ['event_type' => 'spin_cost']);
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

    private function startGameSession(int $level = 1): GameSession
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return app(GameEngine::class)->start($user, $level);
    }
}
