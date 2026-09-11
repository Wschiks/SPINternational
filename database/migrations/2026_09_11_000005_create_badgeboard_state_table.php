<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badgeboard_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->json('icon_states');

            $table->boolean('vertical_col1_claimed')->default(false);
            $table->boolean('vertical_col2_claimed')->default(false);
            $table->boolean('vertical_col4_claimed')->default(false);
            $table->boolean('vertical_col5_claimed')->default(false);

            $table->boolean('horizontal_row1_complete')->default(false);
            $table->boolean('horizontal_row2_complete')->default(false);
            $table->boolean('horizontal_row3_complete')->default(false);
            $table->boolean('horizontal_row4_complete')->default(false);

            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badgeboard_state');
    }
};
