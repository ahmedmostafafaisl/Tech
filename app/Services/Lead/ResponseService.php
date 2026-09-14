<?php

namespace App\Services\Lead;

use App\Models\LeadResponse;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResponseService
{
    /**
     * Generate a short unique reference number, e.g. REF-A3F2C1.
     */
    public function generateRefNumber(): string
    {
        do {
            $ref = 'REF-' . strtoupper(Str::random(6));
        } while (LeadResponse::where('ref_number', $ref)->exists());

        return $ref;
    }

    /**
     * Save a customer response for a given lead and question.
     * Resolves the score automatically from question_options.
     *
     * @throws \InvalidArgumentException  if the answer does not match any option
     */
    public function saveResponse(string $leadId, int $questionNo, string $answerText): LeadResponse
    {
        $option = QuestionOption::resolveAnswer($questionNo, $answerText);

        return LeadResponse::create([
            'lead_id'     => $leadId,
            'ref_number'  => $this->generateRefNumber(),
            'question_no' => $questionNo,
            'answer_text' => trim($answerText),
            'score'       => $option->score,
            'answered_at' => now(),
        ]);
    }

    /**
     * Get all responses for a lead, ordered by question number.
     *
     * @return Collection<LeadResponse>
     */
    public function getResponsesForLead(string $leadId): Collection
    {
        return LeadResponse::where('lead_id', $leadId)
            ->orderBy('question_no')
            ->get();
    }

    /**
     * Get a specific question's response for a lead.
     */
    public function getResponse(string $leadId, int $questionNo): ?LeadResponse
    {
        return LeadResponse::where('lead_id', $leadId)
            ->where('question_no', $questionNo)
            ->first();
    }
}
