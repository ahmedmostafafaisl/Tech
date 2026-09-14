<?php

namespace App\Helper;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseHelper
{
    public static function sendNotification($token, $title, $body, $extraData = [])
    {
        try {
            // ✅ Path to Firebase service account file
            $serviceAccountPath = storage_path('service-account.json');

            if (!file_exists($serviceAccountPath)) {
                throw new \Exception("Firebase service account file not found at $serviceAccountPath");
            }

            // ✅ Authenticate Google Client
            $client = new GoogleClient();
            $client->setAuthConfig($serviceAccountPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];
            $projectId = json_decode(file_get_contents($serviceAccountPath), true)['project_id'];

            // ✅ Ensure we send notificationType + order_id flat under "data"
            $data = array_map(fn($v) => (string)$v, $extraData);


            // ✅ Firebase message payload (v1 format)
            $payload = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "title" => $title,
                        "body" => $body,
                    ],
                    "data" => $data,
                ],
            ];


            // ✅ Send request to FCM
            $response = Http::withToken($accessToken)
                ->withHeaders(["Content-Type" => "application/json"])
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            Log::info('Firebase Notification Sent', [
                'token' => $token,
                'title' => $title,
                'payload' => $payload,
                'response' => $response->json(),
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Firebase Notification Failed', [
                'error' => $e->getMessage(),
                'token' => $token,
                'title' => $title,
            ]);

            return ['error' => $e->getMessage()];
        }
    }
}
