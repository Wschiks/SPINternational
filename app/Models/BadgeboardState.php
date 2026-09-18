<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BadgeboardState extends Model
{
    protected $table = 'badgeboard_state';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'icon_states' => 'array',
            'vertical_col1_claimed' => 'boolean',
            'vertical_col2_claimed' => 'boolean',
            'vertical_col4_claimed' => 'boolean',
            'vertical_col5_claimed' => 'boolean',
            'horizontal_row1_complete' => 'boolean',
            'horizontal_row2_complete' => 'boolean',
            'horizontal_row3_complete' => 'boolean',
            'horizontal_row4_complete' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public static function emptyGrid(): array
    {
        // row => [col => value]; value: 0 = off, 1 = on, null = middle column (theme slot).
        $grid = [];
        for ($row = 1; $row <= 4; $row++) {
            $grid[$row] = [1 => 0, 2 => 0, 3 => null, 4 => 0, 5 => 0];
        }

        return $grid;
    }
}
