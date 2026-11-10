<?php

namespace App\Http\Controllers\Api\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\EvaluationMessage;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;

class EvaluationMessageController extends Controller
{

    public function __construct(
        private readonly WhatsAppService $whatsAppService,
    ) {}
    public function index(Request $request)
    {
        $query = EvaluationMessage::query()
            ->when($request->filled('phone'), function ($query) use ($request) {
                $query->where('phone', $request->phone);
            })
            ->when($request->filled('book_id'), function ($query) use ($request) {
                $query->where('book_id', $request->book_id);
            })
            ->when($request->filled('order_type'), function ($query) use ($request) {
                $query->where('order_type', $request->order_type);
            })
            ->when($request->has('sent'), function ($query) use ($request) {
                $query->where(
                    'sent',
                    filter_var($request->sent, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $request->sent
                );
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->to_date);
            })
            ->latest();

        $perPage = max(1, (int) $request->input('per_page', 10));

        // Support both "current_page" and "page"
        $currentPage = (int) $request->input(
            'current_page',
            $request->input('page', 1)
        );

        $currentPage = max(1, $currentPage);

        $total = (clone $query)->count();

        $totalPages = max(1, (int) ceil($total / $perPage));

        // Prevent requesting a page beyond the last page
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $items = $query
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'data' => $items,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages'  => $totalPages,
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'order_type' => ['required', 'string'],
            'sent' => ['boolean'],
        ]);

        $evaluationMessage = EvaluationMessage::create($data);

        return response()->json($evaluationMessage, 201);
    }

    public function show(EvaluationMessage $evaluationMessage)
    {
        return $evaluationMessage;
    }

    public function update(Request $request, EvaluationMessage $evaluationMessage)
    {
        $data = $request->validate([
            'phone' => ['sometimes', 'string'],
            'order_type' => ['sometimes', 'string'],
            'sent' => ['sometimes', 'boolean'],
        ]);

        $evaluationMessage->update($data);

        return response()->json($evaluationMessage);
    }

    public function destroy(EvaluationMessage $evaluationMessage)
    {
        $evaluationMessage->delete();

        return response()->noContent();
    }

    public function getByPhone(string $phone)
    {
        $evaluationMessage = EvaluationMessage::where('phone', $phone)
            ->latest()
            ->first();

        if (! $evaluationMessage) {
            return response()->json([
                'message' => 'Evaluation message not found.'
            ], 404);
        }

        return response()->json([
            'data' => $evaluationMessage
        ]);
    }


    public function createAndSendEvaluation(string $phone, string $orderType, string $bookId)
    {
        // Get existing evaluation or create a new one
        $evaluation = EvaluationMessage::firstOrCreate(
            ['book_id' => $bookId],
            [
                'phone'      => $phone,
                'order_type' => trim($orderType),
                'sent'       => false,
            ]
        );

        // Already sent? Don't send again.
        if ($evaluation->sent) {
            return response()->json([
                'message' => 'Evaluation already sent.',
                'evaluation' => $evaluation,
            ]);
        }

        $orderType = trim($evaluation->order_type);

        if (! in_array($orderType, ['تركيب', 'صيانة دورية', 'خدمات', 'منتجات', 'صيانة طارئة'], true)) {
            return response()->json([
                'message' => 'Unsupported order type.',
            ], 400);
        }

        // One template for every evaluation type now (sales, after-sales, products, emergency maintenance)
        // — what used to pick a different approved template per type instead picks a different evaluation
        // page via EvaluationLinkResolver, sent as this one template's body parameter. The template name
        // itself stays a Setting (same pattern as invoice_whatsapp_template_name) so it can be changed
        // without a code deploy; customer_evaluation_link is the already-approved template this defaults to.
        $templateName = \App\Models\Setting::get('evaluation_whatsapp_template_name', 'customer_evaluation_link');

        // Falls back to $evaluation->phone (the originally-passed/stored number) when DY365's own
        // CustomerPhoneNumber isn't available — never changes what's stored on the EvaluationMessage record
        // itself (still keyed, found and created by book_id, same as before), only who the WhatsApp message
        // actually goes to.
        $recipientPhone = $evaluation->phone;

        try {
            $evaluationLinkData = app(\App\Services\Evaluation\EvaluationLinkDataFetcher::class)->fetchForBookId($bookId);

            if (! empty($evaluationLinkData['customer_phone'])) {
                $recipientPhone = $evaluationLinkData['customer_phone'];
            }

            $fullLink = \App\Services\Evaluation\EvaluationLinkResolver::buildWithEncryptedData($orderType, $evaluationLinkData);

            // Shortened before being sent — the encrypted data query param alone can run 300+ characters,
            // and Meta rejects the whole template send once the rendered body exceeds 1024 characters
            // (confirmed in production: #132005 "Translated text too long").
            $evaluationLink = \App\Services\Evaluation\EvaluationLinkResolver::shorten($fullLink, $bookId, $recipientPhone, $evaluationLinkData);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('createAndSendEvaluation: could not fetch evaluation link data, sending plain link', [
                'book_id' => $bookId,
                'error'   => $e->getMessage(),
            ]);

            $evaluationLink = \App\Services\Evaluation\EvaluationLinkResolver::resolve($orderType);
        }

        // Send WhatsApp message
        $sendEvaluation = $this->whatsAppService->sendSalesEvaluation(
            $recipientPhone,
            $templateName,
            $evaluationLink
        );

        $response = $sendEvaluation->json();

        // Mark as sent if accepted
        if (
            $sendEvaluation->successful() &&
            data_get($response, 'messages.0.message_status') === 'accepted'
        ) {
            $evaluation->update([
                'sent' => true,
            ]);
        }

        return response()->json([
            'evaluation' => $evaluation->fresh(),
            'whatsapp'   => $response,
        ], $sendEvaluation->status());
    }
}
