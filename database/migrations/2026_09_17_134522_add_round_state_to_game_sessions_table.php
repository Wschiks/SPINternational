<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            // Current spin outcome, waiting to be resolved via hold/answer.
            $table->json('current_reel_result')->nullable()->after('level_selected');
            $table->string('current_match_type', 10)->nullable()->after('current_reel_result');

            // Active question round (set by hold(), cleared after answer/skip/reject).
            $table->unsignedInteger('current_category_id')->nullable()->after('current_match_type');
            $table->unsignedInteger('current_question_id')->nullable()->after('current_category_id');
            $table->unsignedInteger('current_points')->nullable()->after('current_question_id');

            // Theme round in progress (set after theme/select, cleared after theme-answer).
            $table->unsignedInteger('current_theme_id')->nullable()->after('current_points');
            $table->unsignedInteger('current_theme_question_id')->nullable()->after('current_theme_id');

            // Anti-repeat pools.
            $table->json('used_question_ids')->nullable()->after('current_theme_question_id');
            $table->json('used_theme_question_ids')->nullable()->after('used_question_ids');

            $table->boolean('led_krans_pending')->default(false)->after('used_theme_question_ids');
            $table->unsignedInteger('theme_credits')->default(0)->after('led_krans_pending');

            $table->integer('current_score')->default(0)->after('theme_credits');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'current_reel_result',
                'current_match_type',
                'current_category_id',
                'current_question_id',
                'current_points',
                'current_theme_id',
                'current_theme_question_id',
                'used_question_ids',
                'used_theme_question_ids',
                'led_krans_pending',
                'theme_credits',
                'current_score',
            ]);
        });
    }
};
