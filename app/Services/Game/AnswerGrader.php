<?php

namespace App\Services\Game;

class AnswerGrader
{
    /**
     * @param  mixed  $correctAnswer  Shape depends on $type (see Question::correct_answer / hotspot_coords).
     * @param  mixed  $userAnswer  Raw value submitted by the client.
     */
    public static function isCorrect(string $type, mixed $correctAnswer, mixed $userAnswer, mixed $hotspotCoords = null): bool
    {
        return match ($type) {
            'mc_1goed', 'waar_niet', 'foto', 'blitz', 'zoom' => is_string($userAnswer)
                && strtolower($userAnswer) === strtolower((string) $correctAnswer),

            'mc_2goed', 'mc_3goed' => self::sameSet($correctAnswer, $userAnswer),

            'volgorde' => self::sameSequence($correctAnswer, $userAnswer),

            'matching', 'sleep' => self::samePairs($correctAnswer, $userAnswer),

            'schatting' => self::withinRange($correctAnswer, $userAnswer),

            'invulzin' => self::matchesAny($correctAnswer, $userAnswer),

            'hotspot' => self::insideHotspot($hotspotCoords, $userAnswer),

            default => false,
        };
    }

    private static function sameSet(mixed $correct, mixed $user): bool
    {
        if (! is_array($correct) || ! is_array($user)) {
            return false;
        }
        $c = array_map('strtolower', array_map('strval', $correct));
        $u = array_map('strtolower', array_map('strval', $user));
        sort($c);
        sort($u);

        return $c === $u;
    }

    private static function sameSequence(mixed $correct, mixed $user): bool
    {
        if (! is_array($correct) || ! is_array($user) || count($correct) !== count($user)) {
            return false;
        }

        return array_values(array_map('strval', $correct)) === array_values(array_map('strval', $user));
    }

    private static function samePairs(mixed $correct, mixed $user): bool
    {
        if (! is_array($correct) || ! is_array($user)) {
            return false;
        }

        $normalize = function (array $pairs) {
            $out = [];
            foreach ($pairs as $pair) {
                $from = strtolower((string) ($pair['from'] ?? $pair[0] ?? ''));
                $to = strtolower((string) ($pair['to'] ?? $pair[1] ?? ''));
                $out[] = "$from:$to";
            }
            sort($out);

            return $out;
        };

        return $normalize($correct) === $normalize($user);
    }

    private static function withinRange(mixed $correct, mixed $user): bool
    {
        if (! is_array($correct) || ! isset($correct['min'], $correct['max']) || ! is_numeric($user)) {
            return false;
        }

        return (float) $user >= (float) $correct['min'] && (float) $user <= (float) $correct['max'];
    }

    private static function matchesAny(mixed $correct, mixed $user): bool
    {
        if (! is_array($correct) || ! is_string($user)) {
            return false;
        }
        $u = strtolower(trim($user));

        foreach ($correct as $accepted) {
            if (strtolower(trim((string) $accepted)) === $u) {
                return true;
            }
        }

        return false;
    }

    /** $hotspotCoords: [[{x,y}, ...]] one polygon of percentage points. $user: {x,y}. */
    private static function insideHotspot(mixed $hotspotCoords, mixed $user): bool
    {
        if (! is_array($hotspotCoords) || ! isset($hotspotCoords[0]) || ! is_array($user)) {
            return false;
        }

        $polygon = $hotspotCoords[0];
        $x = (float) ($user['x'] ?? -1);
        $y = (float) ($user['y'] ?? -1);

        $inside = false;
        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = (float) $polygon[$i]['x'];
            $yi = (float) $polygon[$i]['y'];
            $xj = (float) $polygon[$j]['x'];
            $yj = (float) $polygon[$j]['y'];

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 0.0001) + $xi);

            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
