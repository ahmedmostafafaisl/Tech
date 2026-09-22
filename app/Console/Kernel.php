<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('dynamics:sync-warehouses')->hourly();
        // $schedule->command('dynamics:sync-categories')->hourly();
        // $schedule->command('dynamics:sync-technicians')->everyThirtyMinutes();
        // $schedule->command('sync:warehouse-stock')->dailyAt('03:00');
        // $schedule->command('sync:main-warehouse-stock')->hourly();
        // $schedule->command('technicians:sync-stock')->dailyAt('03:00');
        // $schedule->command('sync:technician-transfers')->everyThirtyMinutes();
        // $schedule->command('all:sync-appointments')->everyThirtyMinutes();
        // $schedule->command('technicians:sync-appointments')->hourly();
        // $schedule->command('sync:technician-status-requests')->everyThirtyMinutes();
        // $schedule->command('dynamics:sync-customers')->hourly()->withoutOverlapping();
        // $schedule->command('appointments:send-confirmations')->dailyAt('09:00');
        // $schedule->command('dy365:complete-appointments')->dailyAt('02:00');
        // $schedule->command('dy365:complete-appointments')->hourly();
        // $schedule->command('appointments:check-non-completed')->everyThreeHours();
        // $schedule->command('appointments:send-non-completed-reminders')->everyThreeHours();
        // $schedule->command('reports:send-daily-confirmed-export-links')->timezone('Asia/Riyadh')->dailyAt('09:00')->unlessEmergency();

        $schedule->job(new \App\Jobs\GenerateMonthlyPreMessagesExportLink())->timezone('Asia/Riyadh')->dailyAt('04:00')->unlessEmergency();
    }

    /**
     * Register the commands for the application.
     */
    protected $commands = [
        \App\Console\Commands\SyncWarehouses::class,
        \App\Console\Commands\SyncTechnicians::class,
        \App\Console\Commands\ResendFailedWhatsAppMessages::class,
        \App\Console\Commands\RefreshInvoiceShortLink::class,
        \App\Console\Commands\SyncCustomers::class,
        \App\Console\Commands\SyncCategories::class,
        // \App\Console\Commands\SyncTechnicianStock::class,
        // \App\Console\Commands\SyncCustomTechnicianStock::class,
        // \App\Console\Commands\SyncAllTechnicianTransfers::class,
        // \App\Console\Commands\SyncTechnicianAppointments::class,
        // \App\Console\Commands\SyncAllAppointments::class,
        // \App\Console\Commands\SyncWarehouseStockCommand::class,
        // \App\Console\Commands\SyncMainWarehouseStockCommand::class,
        // \App\Console\Commands\SyncCustomMainWarehouseStockCommand::class,
        // \App\Console\Commands\SyncTechnicianChangeStatusRequestsCommand::class,
        // \App\Console\Commands\SyncSingleWarehouseStockCommand::class,
        // \App\Console\Commands\SyncTechnicianStockCommand::class,
        // \App\Console\Commands\SyncStockByTech::class,
        // \App\Console\Commands\SyncWarehouseStockItemCommand::class,
        // \App\Console\Commands\SyncTechnicianStockItemCommand::class,
        // \App\Console\Commands\SyncAndResetTechnicianStock::class,
        \App\Console\Commands\SendAppointmentConfirmations::class,
        \App\Console\Commands\SyncDy365Appointments::class,
        \App\Console\Commands\CompleteSuccessPaymentsCommand::class,
        \App\Console\Commands\SendNonCompletedAppointmentsReminder::class,
        \App\Console\Commands\CheckNonCompletedAppointments::class,
        \App\Console\Commands\CheckAppointmentAmountDifferences::class,
        \App\Console\Commands\CheckAppointmentTech_id::class,
        \App\Console\Commands\SendNonCompletedAppointmentsDyReminder::class,
        \App\Console\Commands\CheckAppointmentAmountDifferencesDy::class,
        \App\Console\Commands\ProcessTamaraOrders::class,
        \App\Console\Commands\ImportSalesOrderPayments::class,
        \App\Console\Commands\SendCustomerResponsesToDyCommand::class,
        \App\Console\Commands\SendAttachmentsToDynamicsCommand::class,
        \App\Console\Commands\LoadFonts::class,
        \App\Console\Commands\SendSubmissionToDynamics::class,
        \App\Console\Commands\ResendPreAppointmentMessages::class,
        \App\Console\Commands\ResendEvaluationMessages::class,
        \App\Console\Commands\SendDailyConfirmedExportLinks::class,



    ];
}
