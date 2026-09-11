<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->integer('points_delta');
            $table->integer('cumulative_score');
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->timestamp('timestamp')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_events');
    }
};
