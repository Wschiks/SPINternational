<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->json('held_reels')->nullable()->after('current_reel_result');
            $table->unsignedTinyInteger('current_reel_index')->nullable()->after('current_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['held_reels', 'current_reel_index']);
        });
    }
};
