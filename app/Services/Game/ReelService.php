<?php

namespace App\Services\Game;

use App\Models\GameSession;
use App\Support\GameCatalog;
use RuntimeException;

/**
 * Spinning, holding and unholding the 3 reels. A round is at most 2 spins, like a
 * fruit machine: hold reels after the first spin, and a question only comes once
 * all 3 reels are held on the same symbol. The spin after that starts a new round.
 */
class ReelService
{
    public function __construct(private QuestionService $questions) {}

    public function spin(GameSession $session): GameSession
    {
        $categoryIds = array_keys(GameCatalog::CATEGORIES);
        $held = $session->held_reels ?? [];
        $previous = $session->current_reel_result ?? [null, null, null];
        $roundSpins = $session->round_spins;

        // A new round (after 2 spins, or with all 3 reels held) re-rolls every reel.
        if (count($held) === 3 || $roundSpins >= 2) {
            $held = [];
            $roundSpins = 0;
        }

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
            'held_reels' => $held,
            'round_spins' => $roundSpins + 1,
            'used_reels' => [],
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

    /**
     * @return array{question: array<string, mixed>|null, points: int|null, match_type: string|null}
     */
    public function hold(GameSession $session, int $reelNumber): array
    {
        $reels = $session->current_reel_result;
        if (! $reels || $reelNumber < 1 || $reelNumber > 3) {
            throw new RuntimeException(__('No active spin to hold.'));
        }
        if ($session->current_question_id) {
            throw new RuntimeException(__('A question is already in progress.'));
        }
        if (in_array($reelNumber, $session->held_reels ?? [], true)) {
            throw new RuntimeException(__('That reel is already held.'));
        }
        if (in_array($reelNumber, $session->used_reels ?? [], true)) {
            throw new RuntimeException(__('Deze rol heb je deze spin al gebruikt.'));
        }

        $held = [...($session->held_reels ?? []), $reelNumber];
        $session->update(['held_reels' => $held]);

        if (count($held) < 3 || count(array_unique($reels)) > 1) {
            return ['question' => null, 'points' => null, 'match_type' => $session->current_match_type];
        }

        $categoryId = $reels[$reelNumber - 1];
        $points = $this->pointsForMatch(3, $session->level_selected);

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

    /** Marks the current reel as answered, so it can't be held again this spin. */
    public function markCurrentReelUsed(GameSession $session): void
    {
        if (! $session->current_reel_index) {
            return;
        }

        $used = $session->used_reels ?? [];
        $used[] = $session->current_reel_index;
        $session->update(['used_reels' => array_values(array_unique($used))]);
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
