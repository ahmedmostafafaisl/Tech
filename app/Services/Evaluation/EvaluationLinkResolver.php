<?php

namespace App\Services\Evaluation;

/**
 * Maps an appointment's order_type to its evaluation/rating page — conceptually the same four-way split
 * EvaluationMessageController::createAndSendEvaluation() uses to pick a WhatsApp template name, kept here
 * as its own thing since that method needs a template name and this needs a URL; the categories themselves
 * must stay in sync between the two if either changes.
 */
class EvaluationLinkResolver
{
    public static function resolve(?string $orderType): ?string
    {
        $orderType = trim((string) $orderType);

        return match (true) {
            in_array($orderType, ['تركيب'], true) => 'https://www.naqi.sa/rate-sales/',
            in_array($orderType, ['صيانة دورية', 'خدمات'], true) => 'https://www.naqi.sa/rate-after-sales-service/',
            in_array($orderType, ['منتجات'], true) => 'https://www.naqi.sa/rate-products/',
            in_array($orderType, ['صيانة طارئة'], true) => 'https://www.naqi.sa/rate-urgent-maintenance/',
            default => null,
        };
    }
}
