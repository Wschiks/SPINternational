<?php

namespace App\Services\Game;

use App\Models\BadgeboardState;
use App\Models\GameSession;
use App\Models\Leaderboard;
use App\Models\Question;
use App\Models\ScoreEvent;
use App\Models\ThemeQuestion;
use App\Models\User;
use App\Support\GameCatalog;
use RuntimeException;

class GameEngine
{
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

        BadgeboardState::create([
            'session_id' => $session->id,
            'icon_states' => BadgeboardState::emptyGrid(),
        ]);

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

        $categoryIds = array_keys(GameCatalog::CATEGORIES);
        $held = $session->held_reels ?? [];
        $previous = $session->current_reel_result ?? [null, null, null];

        // Held reels keep their symbol; only the unheld reels re-roll.
        $reels = [];
        for ($i = 1; $i <= 3; $i++) {
            $reels[$i - 1] = in_array($i, $held, true)
                ? $previous[$i - 1]
                : $categoryIds[array_rand($categoryIds)];
        }

        $counts = array_count_values($reels);
        $matchType = max($counts) >= 3 ? 'triple' : (max($counts) === 2 ? 'double' : 'none');

        $session->update([
            'current_reel_result' => $reels,
            'current_match_type' => $matchType,
            'current_category_id' => null,
            'current_question_id' => null,
            'current_points' => null,
            'current_reel_index' => null,
        ]);

        return $session;
    }

    public function unhold(GameSession $session, int $reelNumber): GameSession
    {
        $this->assertActive($session);

        if ($session->current_question_id) {
            throw new RuntimeException('Cannot unhold while a question is active.');
        }

        $held = array_values(array_diff($session->held_reels ?? [], [$reelNumber]));
        $session->update(['held_reels' => $held]);

        return $session;
    }

    public function hold(GameSession $session, int $reelNumber): array
    {
        $this->assertActive($session);

        $reels = $session->current_reel_result;
        if (! $reels || $reelNumber < 1 || $reelNumber > 3) {
            throw new RuntimeException('No active spin to hold.');
        }
        if ($session->current_question_id) {
            throw new RuntimeException('A question is already in progress.');
        }
        if (in_array($reelNumber, $session->held_reels ?? [], true)) {
            throw new RuntimeException('That reel is already held.');
        }

        $categoryId = $reels[$reelNumber - 1];
        $matchCount = count(array_filter($reels, fn ($c) => $c === $categoryId));
        $points = $this->pointsForMatch($matchCount, $session->level_selected);

        $question = $this->pickQuestion($categoryId, $session->level_selected, $session->used_question_ids ?? []);

        $session->update([
            'current_category_id' => $categoryId,
            'current_question_id' => $question->id,
            'current_points' => $points,
            'current_reel_index' => $reelNumber,
        ]);

        return [
            'question' => $question->toPublicArray(),
            'points' => $points,
            'match_type' => $session->current_match_type,
        ];
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

        $this->markQuestionUsed($session, $question->id);

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
            $this->awardPoints($session, $points, 'question_correct', $question->id);
            $result['points_awarded'] = $points;

            $result['badgeboard_update'] = $this->activateIcon($session, $question->category_id);
            $result['vertical_bonus'] = $this->checkVerticalBonus($session, $question->category_id);
            $result['horizontal_bonus'] = $this->checkHorizontalBonus($session, $question->category_id);

            $session->update(['led_krans_pending' => true]);
            $result['trigger_led_krans'] = true;

            // Correctly answering a held reel locks it in place — it will
            // keep showing this symbol on future spins until unheld.
            if ($session->current_reel_index) {
                $held = $session->held_reels ?? [];
                $held[] = $session->current_reel_index;
                $session->update(['held_reels' => array_values(array_unique($held))]);
            }
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

        $this->markQuestionUsed($session, $session->current_question_id);
        $this->awardPoints($session, -20, 'penalty_skip', $session->current_question_id);

        $question = $this->pickQuestion($session->current_category_id, $session->level_selected, $session->used_question_ids ?? []);
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

        $this->awardPoints($session, -10, 'penalty_reject', $session->current_question_id);

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
        $this->awardPoints($session, $segment['points'], 'led_bonus', null);
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
        $question = $this->pickThemeQuestion($themeId, $session->level_selected, $session->used_theme_question_ids ?? []);

        $session->update(['current_theme_id' => $themeId, 'current_theme_question_id' => $question->id]);

        return [
            'theme_id' => $themeId,
            'slot' => $slot,
            'question' => $question->toPublicArray(),
            'points' => $this->themePoints($session->level_selected),
        ];
    }

    public function themeAnswer(GameSession $session, int $questionId, mixed $userAnswer): array
    {
        $this->assertActive($session);

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

        $used = $session->used_theme_question_ids ?? [];
        $used[] = $question->id;
        $session->update(['used_theme_question_ids' => array_values(array_unique($used))]);

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
            $points = $this->themePoints($session->level_selected);
            $this->awardPoints($session, $points, 'theme_correct', null);
            $result['points_awarded'] = $points;

            $checksCol = $session->themeChecksColumn($themeId);
            $newChecks = min(3, $session->{$checksCol} + 1);
            $session->update([$checksCol => $newChecks]);

            if ($newChecks >= 3) {
                $this->awardPoints($session, 100, 'theme_completion_bonus', null);
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

    // --- internals -----------------------------------------------------

    private function assertActive(GameSession $session): void
    {
        if ($session->is_won) {
            throw new RuntimeException('This game session is already won.');
        }
    }

    private function pointsForMatch(int $matchCount, int $level): int
    {
        return match (true) {
            $matchCount >= 3 => 50,
            $matchCount === 2 => 40,
            default => $level * 10,
        };
    }

    private function themePoints(int $level): int
    {
        return match ($level) {
            3 => 100,
            2 => 75,
            default => 50,
        };
    }

    private function pickQuestion(int $categoryId, int $level, array $usedIds): Question
    {
        $query = Question::where('category_id', $categoryId)->whereNotIn('id', $usedIds);

        $question = (clone $query)->where('difficulty', $level)->inRandomOrder()->first()
            ?? (clone $query)->inRandomOrder()->first()
            ?? Question::where('category_id', $categoryId)->inRandomOrder()->first();

        if (! $question) {
            throw new RuntimeException("No questions available for category {$categoryId}.");
        }

        return $question;
    }

    private function pickThemeQuestion(int $themeId, int $level, array $usedIds): ThemeQuestion
    {
        $query = ThemeQuestion::where('theme_id', $themeId)->whereNotIn('id', $usedIds);

        $question = (clone $query)->where('difficulty', $level)->inRandomOrder()->first()
            ?? (clone $query)->inRandomOrder()->first()
            ?? ThemeQuestion::where('theme_id', $themeId)->inRandomOrder()->first();

        if (! $question) {
            throw new RuntimeException("No theme questions available for theme {$themeId}.");
        }

        return $question;
    }

    private function markQuestionUsed(GameSession $session, int $questionId): void
    {
        $used = $session->used_question_ids ?? [];
        $used[] = $questionId;
        $session->update(['used_question_ids' => array_values(array_unique($used))]);
    }

    private function awardPoints(GameSession $session, int $delta, string $eventType, ?int $questionId): void
    {
        $newScore = max(0, $session->current_score + $delta);
        $session->update(['current_score' => $newScore]);

        ScoreEvent::create([
            'session_id' => $session->id,
            'event_type' => $eventType,
            'points_delta' => $delta,
            'cumulative_score' => $newScore,
            'question_id' => $questionId,
        ]);
    }

    private function activateIcon(GameSession $session, int $categoryId): array
    {
        $board = $session->badgeboardState()->firstOrCreate([], ['icon_states' => BadgeboardState::emptyGrid()]);
        $grid = $board->icon_states;

        $pos = GameCatalog::badgeboardPosition($categoryId);
        $grid[$pos['row']][$pos['col']] = 1;

        $board->update(['icon_states' => $grid]);

        return ['category_id' => $categoryId, 'row' => $pos['row'], 'col' => $pos['col']];
    }

    private function checkVerticalBonus(GameSession $session, int $categoryId): ?array
    {
        $pos = GameCatalog::badgeboardPosition($categoryId);
        $col = $pos['col'];

        $board = $session->badgeboardState;
        $claimedField = "vertical_col{$col}_claimed";
        if ($board->{$claimedField}) {
            return null;
        }

        $grid = $board->icon_states;
        $count = 0;
        for ($row = 1; $row <= 4; $row++) {
            if (($grid[$row][$col] ?? 0) === 1) {
                $count++;
            }
        }

        if ($count < 4) {
            return null;
        }

        $board->update([$claimedField => true]);
        $this->awardPoints($session, 100, 'vertical_bonus', null);

        return ['column' => $col, 'points' => 100];
    }

    private function checkHorizontalBonus(GameSession $session, int $categoryId): ?array
    {
        $pos = GameCatalog::badgeboardPosition($categoryId);
        $row = $pos['row'];

        $board = $session->badgeboardState;
        $claimedField = "horizontal_row{$row}_complete";
        if ($board->{$claimedField}) {
            return null;
        }

        $grid = $board->icon_states;
        $count = 0;
        foreach (GameCatalog::BADGEBOARD_COLS as $col) {
            if (($grid[$row][$col] ?? 0) === 1) {
                $count++;
            }
        }

        if ($count < 4) {
            return null;
        }

        $board->update([$claimedField => true]);
        $session->update(['theme_credits' => $session->theme_credits + 1]);

        $availableThemes = [];
        foreach (GameCatalog::THEMES as $id => $theme) {
            $activeCol = $session->themeActiveColumn($id);
            $availableThemes[] = [
                'id' => $id,
                'name' => $theme['name'],
                'emoji' => $theme['emoji'],
                'already_active' => (bool) $session->{$activeCol},
            ];
        }

        return ['row' => $row, 'available_themes' => $availableThemes];
    }
}
