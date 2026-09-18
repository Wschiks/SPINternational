<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameSession extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_won' => 'boolean',
            'theme_duurzaamheid_active' => 'boolean',
            'theme_ict_active' => 'boolean',
            'theme_inclusie_active' => 'boolean',
            'theme_wereldburger_active' => 'boolean',
            'led_krans_pending' => 'boolean',
            'current_reel_result' => 'array',
            'held_reels' => 'array',
            'used_question_ids' => 'array',
            'used_theme_question_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badgeboardState(): HasOne
    {
        return $this->hasOne(BadgeboardState::class, 'session_id');
    }

    public function scoreEvents(): HasMany
    {
        return $this->hasMany(ScoreEvent::class, 'session_id');
    }

    public function themeActiveColumn(int $themeId): string
    {
        return \App\Support\GameCatalog::themeColumn($themeId, 'active');
    }

    public function themeChecksColumn(int $themeId): string
    {
        return \App\Support\GameCatalog::themeColumn($themeId, 'checks');
    }
}
