<?php

namespace App\Services\Game;

use App\Models\GameSession;
use App\Support\GameCatalog;
use RuntimeException;

/** Spinning, holding and unholding the 3 reels. */
class ReelService
{
    public function __construct(private QuestionService $questions) {}

    public function spin(GameSession $session): GameSession
    {
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
        $held = array_values(array_diff($session->held_reels ?? [], [$reelNumber]));
        $session->update(['held_reels' => $held]);

        return $session;
    }

    public function hold(GameSession $session, int $reelNumber): array
    {
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

        $question = $this->questions->pickQuestion($categoryId, $session->level_selected, $session->used_question_ids ?? []);

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

    /** Marks a held reel as permanently locked after a correct answer. */
    public function lockCurrentReel(GameSession $session): void
    {
        if (! $session->current_reel_index) {
            return;
        }

        $held = $session->held_reels ?? [];
        $held[] = $session->current_reel_index;
        $session->update(['held_reels' => array_values(array_unique($held))]);
    }

    public function pointsForMatch(int $matchCount, int $level): int
    {
        return match (true) {
            $matchCount >= 3 => 50,
            $matchCount === 2 => 40,
            default => $level * 10,
        };
    }
}
