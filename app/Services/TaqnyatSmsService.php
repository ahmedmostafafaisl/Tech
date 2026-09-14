<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class TaqnyatSmsService
{
    protected $client;
    protected $apiKey;
    protected $senderName_Tech;
    protected $senderName_Care;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.taqnyat.api_key');
        $this->senderName_Tech = config('services.taqnyat.sender_tech');
        $this->senderName_Care = config('services.taqnyat.sender_care');
    }

    public function sendPaymentLink($phone, $link)
    {
        $url = 'https://api.taqnyat.sa/v1/messages';

        $data = [
            'recipients' => [$phone],
            'body' => "Your Payment link is: $link",
            'sender' => $this->senderName_Tech,
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => $data,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Payment link request failed', [
                'url'      => $url,
                'payload'  => $data,
                'status'   => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : null,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send OTP',
                'error'   => $e->getMessage(),
            ];
        }
    }
    public function sendOtp($phone, $otp)
    {
        $url = 'https://api.taqnyat.sa/v1/messages';

        $data = [
            'recipients' => [$phone],
            'body' => "Your OTP code is: $otp",
            'sender' => $this->senderName_Tech,
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => $data,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => 'Failed to send OTP',
                'error'   => $e->getMessage(),
            ];
        }
    }
    // send pdf link
    public function sendPdfLink($phone, $link)
    {
        $url = 'https://api.taqnyat.sa/v1/messages';

        $data = [
            'recipients' => [$phone],
            'body' => "رابط الفاتورة الخاصة بك: $link",
            'sender' => $this->senderName_Care,
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => $data,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => 'فشل في إرسال رابط الفاتورة',
                'error'   => $e->getMessage(),
            ];
        }
    }
}
