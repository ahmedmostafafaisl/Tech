<?php

namespace App\Services\Lead;

use App\Models\OrderLead;
use App\Services\Lead\LeadService;
use App\Services\Lead\ResponseService;
use App\Services\DY365\DyService;

class ScoringService
{
    // Max possible score = 90  (Q1: 40 + Q2: 50)
    private const HOT_THRESHOLD  = 70; // 70–90
    private const WARM_THRESHOLD = 50; // 50–69
    // cold: 25–49

    public function __construct(
        private readonly LeadService     $leadService,
        private readonly ResponseService $responseService,
        private readonly DyService $dyService,
    ) {}

    /**
     * Derive a priority label from a numeric total score.
     */
    public function getPriority(int $score): string
    {
        return match (true) {
            $score >= self::HOT_THRESHOLD  => 'hot',
            $score >= self::WARM_THRESHOLD => 'warm',
            default                        => 'cold',
        };
    }


    public function calculateAndSave(string $leadId): array
    {
        $responses = $this->responseService->getResponsesForLead($leadId);

        if ($responses->count() < 2) {
            throw new \RuntimeException(
                "Lead {$leadId} does not yet have both responses (found {$responses->count()})."
            );
        }

        $q1 = $responses->firstWhere('question_no', 1);
        $q2 = $responses->firstWhere('question_no', 2);

        if (! $q1 || ! $q2) {
            throw new \RuntimeException(
                'Missing response for question ' . (! $q1 ? '1' : '2') . '.'
            );
        }

        $totalScore = $q1->score + $q2->score;
        $priority   = $this->getPriority($totalScore);

        $lead = $this->leadService->getById($leadId);
        $updated = $this->leadService->applyScore($lead, $totalScore, $priority);


        $dyPayload = [
            '_contract' => [
                'TargetId'    => $updated->rec_id,
                'TargetScore' => $totalScore,
            ],
        ];

        try {
            $dyResponse = $this->dyService->updateCallListScore($dyPayload, 0);
        } catch (\Throwable $e) {
            $dyResponse = ['error' => $e->getMessage()];

            \Illuminate\Support\Facades\Log::error('updateCallListScore failed for lead ' . $leadId, [
                'error'   => $e->getMessage(),
                'payload' => $dyPayload,
            ]);
        }

        $updated->update([
            'dy_payload'  => json_encode($dyPayload),
            'dy_response' => json_encode($dyResponse),
        ]);

        return [
            'lead'        => $updated,
            'total_score' => $totalScore,
            'priority'    => $priority,
            'q1_score'    => $q1->score,
            'q2_score'    => $q2->score,
            'dy_response' => $dyResponse,
        ];
    }
}
