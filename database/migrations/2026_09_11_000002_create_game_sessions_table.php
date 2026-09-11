<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->integer('final_score')->default(0);
            $table->unsignedInteger('level_selected')->default(1);
            $table->boolean('is_won')->default(false);

            $table->boolean('theme_duurzaamheid_active')->default(false);
            $table->unsignedInteger('theme_duurzaamheid_checks')->default(0);

            $table->boolean('theme_ict_active')->default(false);
            $table->unsignedInteger('theme_ict_checks')->default(0);

            $table->boolean('theme_inclusie_active')->default(false);
            $table->unsignedInteger('theme_inclusie_checks')->default(0);

            $table->boolean('theme_wereldburger_active')->default(false);
            $table->unsignedInteger('theme_wereldburger_checks')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
