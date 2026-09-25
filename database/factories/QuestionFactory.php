<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => 7,
            'category_name' => 'Nederland',
            'question_type' => 'mc_1goed',
            'question_text' => 'Wat is de hoofdstad van Nederland?',
            'difficulty' => 1,
            'question_number' => fake()->unique()->numberBetween(1, 100000),
            'points_base' => 10,
            'answer_options' => [
                ['key' => 'a', 'text' => 'Amsterdam'],
                ['key' => 'b', 'text' => 'Rotterdam'],
            ],
            'correct_answer' => 'a',
            'feedback_correct' => 'Amsterdam is de hoofdstad.',
            'feedback_wrong' => 'Het juiste antwoord is Amsterdam.',
        ];
    }
}
