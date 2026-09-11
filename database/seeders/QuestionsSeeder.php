<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuestionsSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CHUNK_SIZE = 100;

    public function run(): void
    {
        $path = __DIR__ . '/data/questions.json';

        if (!file_exists($path)) {
            throw new RuntimeException("Seeder data file not found: {$path}");
        }

        $questions = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $rows = array_map(function (array $q) {
            return [
                'category_id'        => $q['category_id'],
                'category_name'      => $q['category_name'],
                'question_type'      => $q['question_type'],
                'question_text'      => $q['question_text'],
                'image_path'         => $q['image_path'],
                'image_type'         => $q['image_type'],
                'difficulty'         => $q['difficulty'],
                'question_number'    => $q['question_number'],
                'points_base'        => $q['points_base'],
                'answer_options'     => $this->toJson($q['answer_options']),
                'correct_answer'     => $this->toJson($q['correct_answer']),
                'hotspot_coords'     => $this->toJson($q['hotspot_coords']),
                'feedback_correct'   => $q['feedback_correct'],
                'feedback_wrong'     => $q['feedback_wrong'],
                'tags'               => $this->toJson($q['tags']),
                'learning_objective' => $q['learning_objective'],
            ];
        }, $questions);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('questions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                DB::table('questions')->insert($chunk);
            }
        });

        $this->command?->info('Seeded ' . count($rows) . ' questions.');
    }

    private function toJson(mixed $value): ?string
    {
        return $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
