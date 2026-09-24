<?php

namespace App\Services\Game;

use App\Models\GameSession;
use App\Models\ScoreEvent;

/** Owns every score change and its audit trail (score_events). */
class ScoreService
{
    public function award(GameSession $session, int $delta, string $eventType, ?int $questionId): int
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

        return $newScore;
    }
}
