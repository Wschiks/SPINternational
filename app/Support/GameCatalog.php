<?php

namespace App\Support;

/**
 * Static metadata for the 16 reel categories and 4 Erasmus+ themes.
 * Badgeboard is a 4x5 grid; column 3 is reserved for theme icons, so the
 * 16 categories fill the remaining 4x4 cells in row-major order.
 */
class GameCatalog
{
    public const CATEGORIES = [
        1 => ['name' => 'Erasmus+', 'emoji' => '🇪🇺'],
        2 => ['name' => 'Europa', 'emoji' => '🌍'],
        3 => ['name' => 'Talenkennis', 'emoji' => '🗣️'],
        4 => ['name' => 'Financien', 'emoji' => '💶'],
        5 => ['name' => 'Pers. Ontw.', 'emoji' => '🧠'],
        6 => ['name' => 'Wereld', 'emoji' => '🗺️'],
        7 => ['name' => 'Nederland', 'emoji' => '🇳🇱'],
        8 => ['name' => 'Milieu', 'emoji' => '♻️'],
        9 => ['name' => 'Keuken', 'emoji' => '🍽️'],
        10 => ['name' => 'Rugzak', 'emoji' => '🎒'],
        11 => ['name' => 'Toerisme', 'emoji' => '🏛️'],
        12 => ['name' => 'Tradities', 'emoji' => '🎭'],
        13 => ['name' => 'Communicatie', 'emoji' => '💬'],
        14 => ['name' => 'I@home', 'emoji' => '🏡'],
        15 => ['name' => 'Euregio', 'emoji' => '🚧'],
        16 => ['name' => 'Beeldvorming', 'emoji' => '📸'],
    ];

    public const THEMES = [
        1 => ['name' => 'Duurzaamheid', 'emoji' => '🌱', 'color' => '#39FF14', 'column' => 'theme_duurzaamheid'],
        2 => ['name' => 'ICT Geletterdheid', 'emoji' => '💻', 'color' => '#00F0FF', 'column' => 'theme_ict'],
        3 => ['name' => 'Sociale Inclusie', 'emoji' => '🫶🏼', 'color' => '#FF006E', 'column' => 'theme_inclusie'],
        4 => ['name' => 'Wereldburgerschap', 'emoji' => '🌐', 'color' => '#FFEA00', 'column' => 'theme_wereldburger'],
    ];

    /** 20 LED krans segments: emoji, points, color. */
    public const LED_KRANS = [
        ['emoji' => '🍀', 'points' => 20, 'color' => 'red'],
        ['emoji' => '🍀', 'points' => 20, 'color' => 'red'],
        ['emoji' => '🍀', 'points' => 20, 'color' => 'red'],
        ['emoji' => '🍀', 'points' => 20, 'color' => 'red'],
        ['emoji' => '🍀', 'points' => 20, 'color' => 'red'],
        ['emoji' => '👍🏼', 'points' => 30, 'color' => 'blue'],
        ['emoji' => '👍🏼', 'points' => 30, 'color' => 'blue'],
        ['emoji' => '👍🏼', 'points' => 30, 'color' => 'blue'],
        ['emoji' => '👍🏼', 'points' => 30, 'color' => 'blue'],
        ['emoji' => '🌈', 'points' => 40, 'color' => 'pink'],
        ['emoji' => '🌈', 'points' => 40, 'color' => 'pink'],
        ['emoji' => '🌈', 'points' => 40, 'color' => 'pink'],
        ['emoji' => '🌈', 'points' => 40, 'color' => 'pink'],
        ['emoji' => '🔥', 'points' => 50, 'color' => 'yellow'],
        ['emoji' => '🔥', 'points' => 50, 'color' => 'yellow'],
        ['emoji' => '🔥', 'points' => 50, 'color' => 'yellow'],
        ['emoji' => '🔥', 'points' => 50, 'color' => 'yellow'],
        ['emoji' => '🎉', 'points' => 100, 'color' => 'green'],
        ['emoji' => '🎉', 'points' => 100, 'color' => 'green'],
        ['emoji' => '🎉', 'points' => 100, 'color' => 'green'],
    ];

    public const BADGEBOARD_COLS = [1, 2, 4, 5];

    public static function badgeboardPosition(int $categoryId): array
    {
        $index = $categoryId - 1; // 0-based
        $row = intdiv($index, 4) + 1; // 1..4
        $col = self::BADGEBOARD_COLS[$index % 4];

        return ['row' => $row, 'col' => $col];
    }

    public static function categoryAt(int $row, int $col): ?int
    {
        $colIndex = array_search($col, self::BADGEBOARD_COLS, true);
        if ($colIndex === false) {
            return null;
        }

        return ($row - 1) * 4 + $colIndex + 1;
    }

    public static function themeColumn(int $themeId, string $suffix): string
    {
        return self::THEMES[$themeId]['column'].'_'.$suffix;
    }
}
