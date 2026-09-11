<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->integer('final_score');
            $table->timestamp('completed_at');
            $table->unsignedInteger('rank')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard');
    }
};
