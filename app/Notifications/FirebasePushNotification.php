<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class FirebasePushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $message;
    protected $notificationType;
    protected $data;

    public function __construct($title, $message, $notificationType = null, $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->notificationType = $notificationType;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database']; // we’ll also manually send via Firebase
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'notification_type' => $this->notificationType,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    public function toFirebase($notifiable)
    {
        if (!$notifiable->fcm_token) {
            return;
        }

        $SERVER_API_KEY = config('services.fcm.server_key');

        $payload = [
            'to' => $notifiable->fcm_token,
            'notification' => [
                'title' => $this->title,
                'body' => $this->message,
                'sound' => 'default',
            ],
            'data' => $this->data,
        ];

        Http::withHeaders([
            'Authorization' => 'key=' . $SERVER_API_KEY,
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);
    }

    public function sendNow($notifiable)
    {
        $this->toFirebase($notifiable);
        $notifiable->notify($this);
    }
}
