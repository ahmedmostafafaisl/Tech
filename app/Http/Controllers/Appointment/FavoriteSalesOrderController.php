<?php

namespace App\Http\Controllers\Appointment;


use App\Http\Controllers\Controller;
use App\Models\FavoriteAppointment;
use App\Models\FavoriteSalesOrder;
use App\Services\DY365\DyService;
use App\Services\Logs\TechnicianAppointmentLogService;
use Illuminate\Http\Request;

class FavoriteSalesOrderController extends Controller
{

    protected $dyService;
    public function __construct(DyService $dyService)
    {
        $this->dyService = $dyService;
    }

    /**
     * 🟢 Get user's favorite sales orders
     */
    public function getFavorites(Request $request)
    {
        $user = $request->user();

        $favorites = $user->favoriteAppointments()
            ->orderByDesc('created_at')
            ->get(['id', 'sales_order_id', 'created_at']);

        return response()->json([
            'status' => true,
            'favorites' => $favorites,
        ]);
    }

    /**
     * 🟡 Add a sales order to favorites
     */
    public function addFavorite(Request $request, $sales_order_id)
    {
        $user = $request->user();

        $favorite = FavoriteAppointment::firstOrCreate([
            'user_id' => $user->id,
            'sales_order_id' => $sales_order_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => "Sales Order {$sales_order_id} added to favorites.",
            'favorite' => $favorite,
        ]);
    }

    public function deleteFavorite(Request $request, $sales_order_id)
    {
        $user = $request->user();

        $deleted = FavoriteAppointment::where('user_id', $user->id)
            ->where('sales_order_id', $sales_order_id)
            ->delete();

        if ($deleted) {
            return response()->json([
                'status' => true,
                'message' => "Sales Order {$sales_order_id} removed from favorites.",
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => "Favorite not found for Sales Order {$sales_order_id}.",
        ], 404);
    }

    /**
     * 🟢 Toggle favorite status for a sales order
     */
    public function toggleFavorite(Request $request, $sales_order_id)
    {
        $request->validate([
            'book_id' => 'required|string|max:255',
        ]);

        $book_id = $request->input('book_id');
        $user = $request->user();
        $techId = $user?->tech_id;

        $logService = app(TechnicianAppointmentLogService::class);

        try {
            $existingFavorite = FavoriteAppointment::where('user_id', $user->id)
                ->where('sales_order_id', $sales_order_id)
                ->first();

            if ($existingFavorite) {
                $existingFavorite->delete();

                $payload = [
                    'bookId' => $book_id,
                    'technicianConfirmation' => false,
                ];

                $response = $this->dyService->techConfirmation($payload);

                $logService->success(
                    techId: $techId,
                    action: 'favorite_removed',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'Appointment removed from favorites',
                    requestPayload: $request->all(),
                    responsePayload: $response,
                    userId: $user->id,
                );

                return response()->json([
                    'status' => true,
                    'favorite' => false,
                    'message' => "Sales Order {$sales_order_id} removed from favorites.",
                ]);
            }

            $favorite = FavoriteAppointment::create([
                'user_id' => $user->id,
                'sales_order_id' => $sales_order_id,
            ]);

            $payload = [
                'bookId' => $book_id,
                'technicianConfirmation' => true,
            ];

            $response = $this->dyService->techConfirmation($payload);

            $logService->success(
                techId: $techId,
                action: 'favorite_added',
                bookId: $book_id,
                salesOrderId: $sales_order_id,
                message: 'Appointment added to favorites',
                requestPayload: $request->all(),
                responsePayload: $response,
                userId: $user->id,
                meta: [
                    'favorite_id' => $favorite->id,
                ],
            );

            return response()->json([
                'status' => true,
                'favorite' => true,
                'message' => "Sales Order {$sales_order_id} added to favorites.",
                'data' => $favorite,
            ]);
        } catch (\Throwable $e) {
            $logService->failed(
                techId: $techId,
                action: 'toggle_favorite',
                bookId: $book_id,
                salesOrderId: $sales_order_id,
                message: 'toggleFavorite failed',
                requestPayload: $request->all(),
                error: $e->getMessage(),
                userId: $user?->id,
                meta: [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
