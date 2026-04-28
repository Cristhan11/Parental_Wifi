<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionBankItem extends Model
{
    use HasFactory;

    public const LEVELS = ['Elementary', 'High School', 'Senior High School'];

    public const SUBJECTS = ['Math', 'English', 'Science'];

    public const STATUSES = ['Active', 'Inactive'];

    protected $fillable = [
        'user_id',
        'level',
        'subject',
        'question_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'explanation',
        'status',
        'source_competency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
