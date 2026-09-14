<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Illuminate\Support\Facades\Http;

class TelegramLogger
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('telegram');

        $level = $config['level'] ?? 'error';

        $logger->pushHandler(new class($level) extends AbstractProcessingHandler {

            public function __construct($level = 'error')
            {
                parent::__construct($this->parseLevel($level), true);
            }

            protected function write(array|\Monolog\LogRecord $record): void
            {
                $message = $record instanceof \Monolog\LogRecord
                    ? $record->message
                    : $record['message'];

                $context = $record instanceof \Monolog\LogRecord
                    ? $record->context
                    : ($record['context'] ?? []);

                $levelName = $record instanceof \Monolog\LogRecord
                    ? $record->level->getName()
                    : ($record['level_name'] ?? 'ERROR');

                $text = "🚨 Laravel Error\n";
                $text .= "Level: {$levelName}\n";
                $text .= "App: " . config('app.name') . "\n";
                $text .= "URL: " . request()->fullUrl() . "\n";
                $text .= "IP: " . request()->ip() . "\n\n";
                $text .= "Message:\n" . $message;

                if (!empty($context['exception'])) {
                    $exception = $context['exception'];

                    $text .= "\n\nException:\n" . get_class($exception);
                    $text .= "\nFile:\n" . $exception->getFile() . ':' . $exception->getLine();
                }

                $text = mb_substr($text, 0, 3900);

                Http::timeout(5)->post(
                    'https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/sendMessage',
                    [
                        'chat_id' => config('services.telegram.chat_id'),
                        'text' => $text,
                    ]
                );
            }

            private function parseLevel($level): Level
            {
                return match (strtolower($level)) {
                    'debug' => Level::Debug,
                    'info' => Level::Info,
                    'notice' => Level::Notice,
                    'warning' => Level::Warning,
                    'error' => Level::Error,
                    'critical' => Level::Critical,
                    'alert' => Level::Alert,
                    'emergency' => Level::Emergency,
                    default => Level::Error,
                };
            }
        });

        return $logger;
    }
}
