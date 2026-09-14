<?php

namespace App\Channels;


use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class FcmChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (! $notifiable->fcm_token) {
            return;
        }

        $data = $notification->toFcm($notifiable);

        Http::withHeaders([
            'Authorization' => 'key=' . config('services.fcm.server_key'),
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $data['token'],
            'notification' => [
                'title' => $data['title'],
                'body' => $data['message'],
                'sound' => 'default',
            ],
            'data' => [
                'notification_type' => $data['notification_type'] ?? 'general',
            ],
        ]);
    }
}
