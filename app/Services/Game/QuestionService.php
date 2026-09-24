<?php

namespace App\Services\Game;

use App\Models\GameSession;
use App\Models\Question;
use App\Models\ThemeQuestion;
use RuntimeException;

/** Picks unused questions for a category/theme and tracks which ones have been used. */
class QuestionService
{
    public function pickQuestion(int $categoryId, int $level, array $usedIds): Question
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

    public function pickThemeQuestion(int $themeId, int $level, array $usedIds): ThemeQuestion
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

    public function markUsed(GameSession $session, int $questionId): void
    {
        $used = $session->used_question_ids ?? [];
        $used[] = $questionId;
        $session->update(['used_question_ids' => array_values(array_unique($used))]);
    }

    public function markThemeUsed(GameSession $session, int $questionId): void
    {
        $used = $session->used_theme_question_ids ?? [];
        $used[] = $questionId;
        $session->update(['used_theme_question_ids' => array_values(array_unique($used))]);
    }
}
