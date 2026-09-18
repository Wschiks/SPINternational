<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'answer_options' => 'array',
            'correct_answer' => 'array',
            'hotspot_coords' => 'array',
            'tags' => 'array',
        ];
    }

    /** Data safe to send to the client before the answer is known. */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
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
