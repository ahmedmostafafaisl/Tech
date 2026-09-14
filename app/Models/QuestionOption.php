<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasUuids;

    protected $table = 'question_options';

    public $timestamps = false;

    protected $fillable = [
        'question_no',
        'option_key',
        'option_text',
        'score',
    ];

    protected $casts = [
        'question_no' => 'integer',
        'score'       => 'integer',
    ];

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /**
     * Resolve a customer's reply to a matching option.
     * Matches by option_key (A/B/C/D) or full option_text, case-insensitive.
     *
     * @throws \InvalidArgumentException
     */
    public static function resolveAnswer(int $questionNo, string $answerText): self
    {
        $normalised = strtolower(trim($answerText));

        // Match by:
        //   1. Compound choice ID:  "Q1A", "Q2C" etc.  (new payload format)
        //   2. Plain option_key:    "A", "B", "C", "D"
        //   3. Full option_text:    "جاهز للحجز والتركيب"
        $options = self::where('question_no', $questionNo)->get();

        $option = $options->first(function ($o) use ($normalised, $questionNo) {
            $compoundKey = strtolower("Q{$questionNo}{$o->option_key}"); // e.g. q1a
            return $compoundKey                      === $normalised
                || strtolower($o->option_key)        === $normalised
                || strtolower($o->option_text)       === $normalised;
        });

        if (! $option) {
            $valid = $options
                ->map(fn($o) => "Q{$questionNo}{$o->option_key} / {$o->option_key} – {$o->option_text}")
                ->implode(' | ');

            throw new \InvalidArgumentException(
                "Unrecognised answer \"{$answerText}\" for question {$questionNo}. Valid: {$valid}"
            );
        }

        return $option;
    }
}
