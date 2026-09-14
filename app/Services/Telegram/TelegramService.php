<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    // Telegram's sendMessage API rejects text over 4096 characters —
    // previously nothing here checked for that, so a long payload (like
    // a transfer order with several serials) could silently fail.
    private const MAX_MESSAGE_LENGTH = 4096;

    public static function send(string $message): bool
    {
        $botToken = config('services.telegram.bot_token') ?? '8249060747:AAH-y5LtSwzoMWfjLkvMxZO-ptKuxQwCPMc';
        $chatId   = config('services.telegram.chat_id') ?? '-5021457521';

        if (empty($botToken) || empty($chatId)) {
            Log::error('TelegramService: bot_token or chat_id is not configured — message not sent.', [
                'has_bot_token' => !empty($botToken),
                'has_chat_id'   => !empty($chatId),
            ]);

            return false;
        }

        if (strlen($message) > self::MAX_MESSAGE_LENGTH) {
            Log::warning('TelegramService: message exceeds 4096 chars, truncating.', [
                'original_length' => strlen($message),
            ]);

            $message = substr($message, 0, self::MAX_MESSAGE_LENGTH - 20) . "\n...[truncated]";
        }

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text'    => $message,
                ]
            );

            if (!$response->successful()) {
                Log::error('TelegramService: send failed.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('TelegramService: exception while sending.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
