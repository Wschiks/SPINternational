<?php

namespace App\Services\Game;

use App\Models\GameSession;
use App\Models\Question;
use App\Models\User;
use App\Support\GameCatalog;
use RuntimeException;

/**
 * Orchestrates a game session by delegating to the focused services below.
 * This class is the only thing GameController talks to — each concern
 * (reels, questions, badgeboard, themes, score) lives in its own class,
 * so different people can work on a feature without touching this file.
 *
 * @see ReelService        spin / hold / unhold
 * @see QuestionService    picking + tracking used questions
 * @see BadgeboardService  icon activation, vertical/horizontal bonuses
 * @see ThemeService       theme selection, theme questions, win condition
 * @see ScoreService       every point change + its audit trail
 */
class GameEngine
{
    public function __construct(
        private ReelService $reels,
        private QuestionService $questions,
        private BadgeboardService $badgeboard,
        private ThemeService $themes,
        private ScoreService $score,
    ) {}

    public function start(User $user, int $level): GameSession
    {
        $level = max(1, min(3, $level));

        $session = GameSession::create([
            'user_id' => $user->id,
            'level_selected' => $level,
            'used_question_ids' => [],
            'used_theme_question_ids' => [],
            'held_reels' => [],
            'current_score' => 0,
        ]);

        $this->badgeboard->createEmpty($session);

        $user->increment('games_played');

        return $session->fresh(['badgeboardState']);
    }

    public function setLevel(GameSession $session, int $level): GameSession
    {
        $this->assertActive($session);

        if ($session->current_question_id || $session->current_theme_question_id) {
            throw new RuntimeException('Cannot change level while a question is active.');
        }

        $session->update(['level_selected' => max(1, min(3, $level))]);

        return $session;
    }

    public function spin(GameSession $session): GameSession
    {
        $this->assertActive($session);

        if ($session->current_question_id) {
            throw new RuntimeException('Cannot spin while a question is active.');
        }

        return $this->reels->spin($session);
    }

    public function unhold(GameSession $session, int $reelNumber): GameSession
    {
        $this->assertActive($session);

        if ($session->current_question_id) {
            throw new RuntimeException('Cannot unhold while a question is active.');
        }

        return $this->reels->unhold($session, $reelNumber);
    }

    public function hold(GameSession $session, int $reelNumber): array
    {
        $this->assertActive($session);

        return $this->reels->hold($session, $reelNumber);
    }

    public function answer(GameSession $session, int $questionId, mixed $userAnswer): array
    {
        $this->assertActive($session);

        if ($session->current_question_id !== $questionId) {
            throw new RuntimeException('That question is no longer active.');
        }

        $question = Question::findOrFail($questionId);
        $correct = AnswerGrader::isCorrect(
            $question->question_type,
            $question->correct_answer,
            $userAnswer,
            $question->hotspot_coords,
        );

        $this->questions->markUsed($session, $question->id);

        $result = [
            'correct' => $correct,
            'points_awarded' => 0,
            'feedback' => $correct ? $question->feedback_correct : $question->feedback_wrong,
            'correct_answer' => $correct ? null : $question->correct_answer,
            'badgeboard_update' => null,
            'vertical_bonus' => null,
            'horizontal_bonus' => null,
            'trigger_led_krans' => false,
        ];

        if ($correct) {
            $points = (int) $session->current_points;
            $this->score->award($session, $points, 'question_correct', $question->id);
            $result['points_awarded'] = $points;

            $result['badgeboard_update'] = $this->badgeboard->activateIcon($session, $question->category_id);
            $result['vertical_bonus'] = $this->badgeboard->checkVerticalBonus($session, $question->category_id);
            $result['horizontal_bonus'] = $this->badgeboard->checkHorizontalBonus($session, $question->category_id);

            $session->update(['led_krans_pending' => true]);
            $result['trigger_led_krans'] = true;

            // Correctly answering a held reel locks it in place — it will
            // keep showing this symbol on future spins until unheld.
            $this->reels->lockCurrentReel($session);
        }

        $session->update([
            'current_category_id' => null,
            'current_question_id' => null,
            'current_points' => null,
            'current_reel_index' => null,
        ]);

        $result['new_score'] = $session->refresh()->current_score;

        return $result;
    }

    public function skip(GameSession $session): array
    {
        $this->assertActive($session);

        if (! $session->current_question_id) {
            throw new RuntimeException('No active question to skip.');
        }

        $this->questions->markUsed($session, $session->current_question_id);
        $this->score->award($session, -20, 'penalty_skip', $session->current_question_id);

        $question = $this->questions->pickQuestion($session->current_category_id, $session->level_selected, $session->used_question_ids ?? []);
        $session->update(['current_question_id' => $question->id]);

        return [
            'penalty' => -20,
            'new_score' => $session->refresh()->current_score,
            'question' => $question->toPublicArray(),
            'points' => $session->current_points,
        ];
    }

    public function reject(GameSession $session): array
    {
        $this->assertActive($session);

        if (! $session->current_question_id) {
            throw new RuntimeException('No active question to reject.');
        }

        $this->score->award($session, -10, 'penalty_reject', $session->current_question_id);

        // Rejecting only cancels this attempt — the reel stays unheld and
        // available, along with the rest of this spin's other reels.
        $session->update([
            'current_category_id' => null,
            'current_question_id' => null,
            'current_points' => null,
            'current_reel_index' => null,
        ]);

        return ['penalty' => -10, 'new_score' => $session->refresh()->current_score];
    }

    public function ledKransStop(GameSession $session, int $index): array
    {
        $this->assertActive($session);

        if (! $session->led_krans_pending) {
            throw new RuntimeException('No LED krans bonus is pending.');
        }

        $segment = GameCatalog::LED_KRANS[$index] ?? GameCatalog::LED_KRANS[0];
        $this->score->award($session, $segment['points'], 'led_bonus', null);
        $session->update(['led_krans_pending' => false]);

        return [
            'emoji' => $segment['emoji'],
            'points' => $segment['points'],
            'new_score' => $session->refresh()->current_score,
        ];
    }

    public function selectTheme(GameSession $session, int $themeId): array
    {
        $this->assertActive($session);

        return $this->themes->select($session, $themeId);
    }

    public function themeAnswer(GameSession $session, int $questionId, mixed $userAnswer): array
    {
        $this->assertActive($session);

        return $this->themes->answer($session, $questionId, $userAnswer);
    }

    public function checkWin(GameSession $session): array
    {
        return $this->themes->checkWin($session);
    }

    public function state(GameSession $session): array
    {
        $session->loadMissing('badgeboardState');
        $board = $session->badgeboardState;

        return [
            'session_id' => $session->id,
            'level_selected' => $session->level_selected,
            'current_score' => $session->current_score,
            'is_won' => $session->is_won,
            'reels' => $session->current_reel_result,
            'match_type' => $session->current_match_type,
            'held_reels' => $session->held_reels ?? [],
            'current_category_id' => $session->current_category_id,
            'current_question_id' => $session->current_question_id,
            'current_theme_id' => $session->current_theme_id,
            'current_theme_question_id' => $session->current_theme_question_id,
            'led_krans_pending' => $session->led_krans_pending,
            'theme_credits' => $session->theme_credits,
            'badgeboard' => [
                'icon_states' => $board->icon_states,
                'verticals' => [
                    1 => $board->vertical_col1_claimed,
                    2 => $board->vertical_col2_claimed,
                    4 => $board->vertical_col4_claimed,
                    5 => $board->vertical_col5_claimed,
                ],
                'horizontals' => [
                    1 => $board->horizontal_row1_complete,
                    2 => $board->horizontal_row2_complete,
                    3 => $board->horizontal_row3_complete,
                    4 => $board->horizontal_row4_complete,
                ],
            ],
            'themes' => [
                1 => ['active' => $session->theme_duurzaamheid_active, 'checks' => $session->theme_duurzaamheid_checks],
                2 => ['active' => $session->theme_ict_active, 'checks' => $session->theme_ict_checks],
                3 => ['active' => $session->theme_inclusie_active, 'checks' => $session->theme_inclusie_checks],
                4 => ['active' => $session->theme_wereldburger_active, 'checks' => $session->theme_wereldburger_checks],
            ],
        ];
    }

    private function assertActive(GameSession $session): void
    {
        if ($session->is_won) {
            throw new RuntimeException('This game session is already won.');
        }
    }
}
