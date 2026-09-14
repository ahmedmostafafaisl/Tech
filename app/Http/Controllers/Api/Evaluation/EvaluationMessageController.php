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

        // Determine template
        $orderType = trim($evaluation->order_type);

        if (in_array($orderType, ['تركيب'])) {
            $template = 'sales_evaluation';
        } elseif (in_array($orderType, ['صيانة دورية', 'خدمات'])) {
            $template = 'after_sales_evaluation';
        } elseif (in_array($orderType, ['منتجات'])) {
            $template = 'products_evaluation';
        } elseif (in_array($orderType, ['صيانة طارئة'])) {
            $template = 'emergency_maintenance_evaluation';
        } else {
            return response()->json([
                'message' => 'Unsupported order type.',
            ], 400);
        }

        // Send WhatsApp message
        $sendEvaluation = $this->whatsAppService->sendSalesEvaluation(
            $evaluation->phone,
            $template
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
