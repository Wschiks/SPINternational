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

    /** Source category_id => theme_id (matches game_sessions theme_* columns). */
    private const THEME_MAP = [
        17 => ['id' => 1, 'name' => 'Duurzaamheid'],
        18 => ['id' => 2, 'name' => 'ICT Geletterdheid'],
        19 => ['id' => 3, 'name' => 'Sociale Inclusie'],
        20 => ['id' => 4, 'name' => 'Wereldburgerschap'],
    ];

    /** Normalizes inconsistent question_type spellings found in the source export. */
    private const TYPE_MAP = [
        'mc 1goed' => 'mc_1goed',
        'mc 2goed' => 'mc_2goed',
        'mc 3goed' => 'mc_3goed',
        'meerkeuze 3goed' => 'mc_3goed',
        'waar/niet' => 'waar_niet',
        'invulzin' => 'invulzin',
        'volgorde' => 'volgorde',
        'matching' => 'matching',
        'schatting' => 'schatting',
        'sleep' => 'sleep',
        'hotspot' => 'hotspot',
        'foto' => 'foto',
        'blitz' => 'blitz',
        'zoom' => 'zoom',
    ];

    public function run(): void
    {
        $path = __DIR__.'/data/questions.json';

        if (! file_exists($path)) {
            throw new RuntimeException("Seeder data file not found: {$path}");
        }

        $questions = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $mainRows = [];
        $themeRows = [];

        foreach ($questions as $q) {
            $type = self::TYPE_MAP[$q['question_type']] ?? $q['question_type'];

            if (isset(self::THEME_MAP[$q['category_id']])) {
                $theme = self::THEME_MAP[$q['category_id']];
                $themeRows[] = [
                    'theme_id' => $theme['id'],
                    'theme_name' => $theme['name'],
                    'question_type' => $type,
                    'question_text' => $q['question_text'],
                    'image_path' => $q['image_path'],
                    'image_type' => $q['image_type'],
                    'difficulty' => $q['difficulty'],
                    'question_number' => $q['question_number'],
                    'points_base' => $q['points_base'],
                    'answer_options' => $this->toJson($q['answer_options']),
                    'correct_answer' => $this->toJson($q['correct_answer']),
                    'hotspot_coords' => $this->toJson($q['hotspot_coords']),
                    'feedback_correct' => $q['feedback_correct'],
                    'feedback_wrong' => $q['feedback_wrong'],
                ];
            } else {
                $mainRows[] = [
                    'category_id' => $q['category_id'],
                    'category_name' => $q['category_name'],
                    'question_type' => $type,
                    'question_text' => $q['question_text'],
                    'image_path' => $q['image_path'],
                    'image_type' => $q['image_type'],
                    'difficulty' => $q['difficulty'],
                    'question_number' => $q['question_number'],
                    'points_base' => $q['points_base'],
                    'answer_options' => $this->toJson($q['answer_options']),
                    'correct_answer' => $this->toJson($q['correct_answer']),
                    'hotspot_coords' => $this->toJson($q['hotspot_coords']),
                    'feedback_correct' => $q['feedback_correct'],
                    'feedback_wrong' => $q['feedback_wrong'],
                    'tags' => $this->toJson($q['tags']),
                    'learning_objective' => $q['learning_objective'],
                ];
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('questions')->truncate();
        DB::table('theme_questions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::transaction(function () use ($mainRows, $themeRows) {
            foreach (array_chunk($mainRows, self::CHUNK_SIZE) as $chunk) {
                DB::table('questions')->insert($chunk);
            }
            foreach (array_chunk($themeRows, self::CHUNK_SIZE) as $chunk) {
                DB::table('theme_questions')->insert($chunk);
            }
        });

        $this->command?->info('Seeded '.count($mainRows).' main questions and '.count($themeRows).' theme questions.');
    }

    private function toJson(mixed $value): ?string
    {
        return $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
