<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;


class SendDailyConfirmedExportLinks extends Command
{
    protected $signature = 'reports:send-daily-confirmed-export-links';

    protected $description = 'Generates yesterday\'s confirmed pre-appointment export links (one per type) and sends them via WhatsApp to a hardcoded number list';

    private array $recipients = [
        '201144431205',
        '201116405941',
    ];

    private array $typeTemplates = [
        'تركيب'  => 'daily_export_link_installation',
        'منتجات' => 'daily_export_link_products',
    ];

    public function handle(
        \App\Repositories\Dashboard\DashboardRepository $repository,
        \App\Services\WhatsApp\WhatsAppConfirmationService $whatsapp
    ) {
        $yesterday = Carbon::yesterday('Asia/Riyadh')->toDateString();

        foreach ($this->typeTemplates as $type => $template) {
            $request = Request::create('/', 'GET', [
                'customer_response' => 'confirm',
                'date'              => $yesterday,
                'type'              => $type,
            ]);

            try {
                $export = $repository->exportPreMessages($request);
            } catch (\Throwable $e) {
                $this->error("Failed to generate export for type '{$type}': {$e->getMessage()}");
                continue;
            }

            if (empty($export['download_url'])) {
                $this->error("No download_url returned for type: {$type}");
                continue;
            }

            $this->info("Generated link for {$type}: {$export['download_url']}");

            foreach ($this->recipients as $phone) {
                try {
                    $response = $whatsapp->sendTemplateMessage(
                        $phone,
                        $template,
                        [
                            ["type" => "text", "text" => $export['download_url']],
                        ],
                        [],
                        'ar'
                    );
                } catch (\Throwable $e) {
                    $this->error("Failed to send to {$phone}: {$e->getMessage()}");
                }
                // Small delay so we don't hammer the WhatsApp API in a tight loop
                usleep(300000);
            }
        }

        return self::SUCCESS;
    }
}
