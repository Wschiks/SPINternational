<?php

namespace App\Services\Game;

use App\Models\BadgeboardState;
use App\Models\GameSession;
use App\Support\GameCatalog;

/** Lights up badgeboard icons and checks the vertical/horizontal bonus lines. */
class BadgeboardService
{
    public function __construct(private ScoreService $score) {}

    public function createEmpty(GameSession $session): BadgeboardState
    {
        return BadgeboardState::create([
            'session_id' => $session->id,
            'icon_states' => BadgeboardState::emptyGrid(),
        ]);
    }

    public function activateIcon(GameSession $session, int $categoryId): array
    {
        $board = $session->badgeboardState()->firstOrCreate([], ['icon_states' => BadgeboardState::emptyGrid()]);
        $grid = $board->icon_states;

        $pos = GameCatalog::badgeboardPosition($categoryId);
        $grid[$pos['row']][$pos['col']] = 1;

        $board->update(['icon_states' => $grid]);

        return ['category_id' => $categoryId, 'row' => $pos['row'], 'col' => $pos['col']];
    }

    public function checkVerticalBonus(GameSession $session, int $categoryId): ?array
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
        $this->score->award($session, 100, 'vertical_bonus', null);

        return ['column' => $col, 'points' => 100];
    }

    public function checkHorizontalBonus(GameSession $session, int $categoryId): ?array
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
