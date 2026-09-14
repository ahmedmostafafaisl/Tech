<?php

namespace App\Http\Controllers\Api\Notification;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Notifications\TechNotification;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    // ✅ Send and store notification
    public function send(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
            'notification_type' => 'required|string',
            'bookId' => 'nullable|string',
            'sales_order' => 'nullable|string',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->fcm_token) {
            return response()->json(['error' => 'User has no FCM token'], 400);
        }

        // ✅ Send via Laravel Notification System (this triggers DB + FCM)
        $user->notify(new TechNotification(
            $request->body,
            $request->title,
            $request->notification_type,
            ['bookId' => $request->bookId ?? null, 'sales_order' => $request->sales_order ?? null]
        ));

        return response()->json(['message' => 'Notification sent & saved']);
    }

    // ✅ Get all user notifications
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->get();
        $unreadNotifications = auth()->user()
            ->unreadNotifications()
            ->latest()
            ->get();
        return response()->json([
            'unreadNotificationsCount' => $unreadNotifications->count(),
            'notifications' => $notifications,
        ]);
    }

    // ✅ Mark single notification as read
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    // ✅ Mark all notifications as read
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }

    // ✅ Get only unread notifications
    public function unread()
    {
        $unreadNotifications = auth()->user()
            ->unreadNotifications()
            ->latest()
            ->get();

        return response()->json([
            'count' => $unreadNotifications->count(),
            'notifications' => $unreadNotifications,
        ]);
    }

    // send tech  notification
    public function sendTechNotification(Request $request)
    {
        // 🧩 Step 1: Validate the incoming data
        $validator = Validator::make($request->all(), [
            'worker_id' => 'required|integer|exists:users,tech_id',
            'notify_type' => 'required|string|in:create,update,confirmation',
            'sales_order' => 'nullable|string|max:255',
            'bookId' => 'nullable|string|max:255',
            'date' => 'required|date_format:d-m-Y',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // 🕒 Step 2: Check if the date is today
        $appointmentDate = Carbon::createFromFormat('d-m-Y', $data['date'])->startOfDay();
        $today = Carbon::today();

        if (!$appointmentDate->equalTo($today)) {
            return response()->json([
                'message' => 'Notification not sent — appointment date is not today.',
                'appointment_date' => $appointmentDate->toDateString(),
                'today' => $today->toDateString(),
            ], 200);
        }

        // 📨 Step 3: Try sending notification
        try {
            $user = User::where('tech_id', $data['worker_id'])->first();

            if (!$user) {
                return response()->json([
                    'error' => 'No user found for this tech_id'
                ], 404);
            }

            if (!$user->fcm_token) {
                return response()->json([
                    'message' => 'User found, but no FCM token registered.'
                ], 200);
            }

            // ✅ Send FCM notification
            $title = match ($data['notify_type']) {
                'update' => 'Appointment Updated',
                'confirmation' => 'Appointment Confirmed',
                default => 'New Appointment Received',
            };

            $body = match ($data['notify_type']) {
                'update' => 'Your appointment details were updated.',
                'confirmation' => 'Your appointment has been confirmed.',
                default => 'You have a new appointment assigned.',
            };

            $user->notify(new TechNotification(
                $body,
                $title,
                $data['notify_type'],
                ['bookId' => $request->bookId ?? null, 'sales_order' => $request->bookId ?? null]
            ));

            return response()->json([
                'message' => 'Notification sent successfully',
                'user' => $user->only(['id', 'username', 'tech_id']),
                'notify_type' => $data['notify_type']
            ]);
        } catch (\Throwable $e) {
            // 🧠 Log error and return JSON response
            Log::error('TechNotification failed: ' . $e->getMessage(), [
                'worker_id' => $data['worker_id'] ?? null,
                ['bookId' => $request->bookId ?? null, 'sales_order' => $request->sales_order ?? null]
            ]);

            return response()->json([
                'error' => 'Failed to send notification',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
