<?php

namespace App\Helper;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FcmHelper
{
    public static function sendNotification($token, $title, $body)
    {
        $SERVER_API_KEY = config('services.fcm.server_key') ?? env('FCM_SERVER_KEY');

        $data = [
            "to" => $token,
            "notification" => [
                "title" => $title,
                "body" => $body,
                "sound" => "default"
            ]
        ];

        $response = Http::withHeaders([
            "Authorization" => "key=$SERVER_API_KEY",
            "Content-Type" => "application/json",
        ])->post("https://fcm.googleapis.com/fcm/send", $data);

        return $response->json();
    }
    public static function sendFcmV1Notification($fcmToken, $title, $body, $type)
    {
        $credentials = json_decode(file_get_contents(storage_path('app/firebase/firebase_credentials.json')), true);


        $accessToken = self::getGoogleAccessToken(credentials: $credentials);

        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send", [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'type' => $type,
                    ],
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                    ],
                ],
            ]);

        return $response->json();
    }

    private static function getGoogleAccessToken($credentials)
    {
        $now = time();
        $payload = [
            'iss' => $credentials['client_email'],
            'sub' => $credentials['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ];

        $jwt = JWT::encode($payload, $credentials['private_key'], 'RS256');

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $response->json()['access_token'];
    }
}
