<?php

namespace App\Services\Game;

use App\Models\GameSession;
use App\Models\Leaderboard;
use App\Models\ThemeQuestion;
use RuntimeException;

/** Erasmus+ theme selection, theme questions, and the win condition. */
class ThemeService
{
    public function __construct(
        private QuestionService $questions,
        private ScoreService $score,
    ) {}

    public function select(GameSession $session, int $themeId): array
    {
        $activeCol = $session->themeActiveColumn($themeId);
        $checksCol = $session->themeChecksColumn($themeId);
        $isActive = (bool) $session->{$activeCol};

        if (! $isActive) {
            if ($session->theme_credits < 1) {
                throw new RuntimeException('No theme selection available right now.');
            }
            $session->update([$activeCol => true, 'theme_credits' => $session->theme_credits - 1]);
        }

        $slot = $session->{$checksCol} + 1; // next open checkbox (1..3)
        $question = $this->questions->pickThemeQuestion($themeId, $session->level_selected, $session->used_theme_question_ids ?? []);

        $session->update(['current_theme_id' => $themeId, 'current_theme_question_id' => $question->id]);

        return [
            'theme_id' => $themeId,
            'slot' => $slot,
            'question' => $question->toPublicArray(),
            'points' => $this->pointsForLevel($session->level_selected),
        ];
    }

    public function answer(GameSession $session, int $questionId, mixed $userAnswer): array
    {
        if ($session->current_theme_question_id !== $questionId || ! $session->current_theme_id) {
            throw new RuntimeException('That theme question is no longer active.');
        }

        $themeId = $session->current_theme_id;
        $question = ThemeQuestion::findOrFail($questionId);
        $correct = AnswerGrader::isCorrect(
            $question->question_type,
            $question->correct_answer,
            $userAnswer,
            $question->hotspot_coords,
        );

        $this->questions->markThemeUsed($session, $question->id);

        $result = [
            'correct' => $correct,
            'points_awarded' => 0,
            'feedback' => $correct ? $question->feedback_correct : $question->feedback_wrong,
            'correct_answer' => $correct ? null : $question->correct_answer,
            'theme_complete' => false,
            'theme_completion_bonus' => 0,
            'trigger_led_krans' => false,
            'game_won' => false,
        ];

        if ($correct) {
            $points = $this->pointsForLevel($session->level_selected);
            $this->score->award($session, $points, 'theme_correct', null);
            $result['points_awarded'] = $points;

            $checksCol = $session->themeChecksColumn($themeId);
            $newChecks = min(3, $session->{$checksCol} + 1);
            $session->update([$checksCol => $newChecks]);

            if ($newChecks >= 3) {
                $this->score->award($session, 100, 'theme_completion_bonus', null);
                $result['theme_complete'] = true;
                $result['theme_completion_bonus'] = 100;
            }

            $session->update(['led_krans_pending' => true]);
            $result['trigger_led_krans'] = true;
        }

        $session->update(['current_theme_id' => null, 'current_theme_question_id' => null]);

        $win = $this->checkWin($session->refresh());
        $result['game_won'] = $win['game_won'];
        $result['new_score'] = $session->refresh()->current_score;

        return $result;
    }

    public function checkWin(GameSession $session): array
    {
        $allActive = $session->theme_duurzaamheid_active && $session->theme_ict_active
            && $session->theme_inclusie_active && $session->theme_wereldburger_active;

        $allChecked = $session->theme_duurzaamheid_checks >= 3 && $session->theme_ict_checks >= 3
            && $session->theme_inclusie_checks >= 3 && $session->theme_wereldburger_checks >= 3;

        $won = $allActive && $allChecked;

        if ($won && ! $session->is_won) {
            $session->update([
                'is_won' => true,
                'completed_at' => now(),
                'final_score' => $session->current_score,
            ]);

            $session->user->increment('games_won');

            Leaderboard::create([
                'user_id' => $session->user_id,
                'session_id' => $session->id,
                'final_score' => $session->current_score,
                'completed_at' => now(),
            ]);
        }

        return ['game_won' => $won];
    }

    private function pointsForLevel(int $level): int
    {
        return match ($level) {
            3 => 100,
            2 => 75,
            default => 50,
        };
    }
}
