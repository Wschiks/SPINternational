<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('theme_id');
            $table->string('theme_name', 50);
            $table->string('question_type', 20);
            $table->text('question_text');
            $table->string('image_path')->nullable();
            $table->string('image_type', 10)->nullable();
            $table->unsignedInteger('difficulty');
            $table->unsignedInteger('question_number');
            $table->unsignedInteger('points_base');

            $table->json('answer_options')->nullable();
            $table->json('correct_answer')->nullable();
            $table->json('hotspot_coords')->nullable();

            $table->text('feedback_correct')->nullable();
            $table->text('feedback_wrong')->nullable();

            $table->unique(['theme_id', 'question_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_questions');
    }
};
