<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Helper\FirebaseHelper;

class TechNotification extends Notification
{
    use Queueable;

    protected $message;
    protected $title;
    protected $notification_type;
    protected $data;

    public function __construct(string $message, string $title, string $notification_type, array $data = [])
    {
        $this->message = $message;
        $this->title = $title;
        $this->notification_type = $notification_type;
        $this->data = $data;
    }

    public function via(object $notifiable): array
    {
        return ['database']; // FCM handled manually
    }

    public function toDatabase($notifiable): array
    {
        // ✅ Prepare clean, flat data for FCM
        $fcmData = array_merge(
            ['notificationType' => $this->notification_type],
            $this->data
        );

        // ✅ Convert all values to string (Firebase only accepts string values)
        $fcmData = array_map(fn($v) => (string) $v, $fcmData);

        // ✅ Send to Firebase
        if (!empty($notifiable->fcm_token)) {
            FirebaseHelper::sendNotification(
                $notifiable->fcm_token,
                $this->title,
                $this->message,
                $fcmData
            );
        }

        // ✅ Store in DB notifications table
        return [
            'title' => $this->title,
            'message' => $this->message,
            'notification_type' => $this->notification_type,
            'data' => $this->data,
        ];
    }
}
