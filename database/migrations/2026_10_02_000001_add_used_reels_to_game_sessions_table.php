<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->json('used_reels')->nullable()->after('held_reels');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn('used_reels');
        });
    }
};
