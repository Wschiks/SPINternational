<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeQuestion extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'answer_options' => 'array',
            'correct_answer' => 'array',
            'hotspot_coords' => 'array',
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'theme_id' => $this->theme_id,
            'theme_name' => $this->theme_name,
            'type' => $this->question_type,
            'text' => $this->question_text,
            'image_path' => $this->image_path,
            'image_type' => $this->image_type,
            'difficulty' => $this->difficulty,
            'options' => $this->answer_options,
            'hotspots' => $this->hotspot_coords,
        ];
    }
}
