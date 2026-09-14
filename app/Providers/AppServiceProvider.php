<?php

namespace App\Providers;

use App\Channels\FcmChannel;
use App\Models\UserStock;
use App\Repositories\Address\AddressRepository;
use App\Repositories\Announcement\AnnouncementRepository;
use App\Repositories\Appointment\AppointmentChangeStatusRequestRepository;
use App\Repositories\Appointment\AppointmentRepository;
use App\Repositories\Appointment\AppointmentStatusChangeRepository;
use App\Repositories\AppointmentTransaction\AppointmentTransactionRepository;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\ChangeRequestReason\ChangeRequestReasonRepository;
use App\Repositories\CompleteForm\CompleteFormRepository;
use App\Repositories\Dashboard\DashboardRepository;
use App\Repositories\Emergency\EmergencyItemConditionRepository;
use App\Repositories\Emergency\EmergencyItemRepository;
use App\Repositories\Interfaces;
use App\Repositories\Interfaces\AddressRepositoryInterface;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use App\Repositories\Interfaces\AppointmentChangeStatusRequestInterface;
use App\Repositories\Interfaces\AppointmentFormSubmissionRepositoryInterface;
use App\Repositories\Interfaces\AppointmentRepositoryInterface;
use App\Repositories\Interfaces\AppointmentStatusChangeRepositoryInterface;
use App\Repositories\Interfaces\AppointmentTransactionInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\ChangeRequestReasonInterface;
use App\Repositories\Interfaces\CompleteFormRepositoryInterface;
use App\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Repositories\Interfaces\EmergencyItemConditionRepositoryInterface;
use App\Repositories\Interfaces\EmergencyItemRepositoryInterface;
use App\Repositories\Interfaces\InvoiceRepositoryInterface;
use App\Repositories\Interfaces\ItemRepositoryInterface;
use App\Repositories\Interfaces\PartRepositoryInterface;
use App\Repositories\Interfaces\RolePermissionInterface;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use App\Repositories\Interfaces\TechRepositoryInterface;
use App\Repositories\Interfaces\TransferOrderInterface;
use App\Repositories\Interfaces\UserLogRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\UserStockRepositoryInterface;
use App\Repositories\Interfaces\WarehouseInterface;
use App\Repositories\Interfaces\WarehouseTransferRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepository;
use App\Repositories\Item\ItemRepository;
use App\Repositories\Part\PartRepository;
use App\Repositories\Roles\RolePermissionRepository;
use App\Repositories\SubmitCompleteForm\AppointmentFormSubmissionRepository;
use App\Repositories\Task\TaskRepository;
use App\Repositories\Tech\TechRepository;
use App\Repositories\Transfer\TransferOrderRepository;
use App\Repositories\Transfer\WarehouseTransferRepository;
use App\Repositories\User\UserLogRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserStockRepository;
use App\Repositories\Warehouse\WarehouseRepository;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AnnouncementRepositoryInterface::class, AnnouncementRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AppointmentRepositoryInterface::class, AppointmentRepository::class);
        $this->app->bind(AppointmentStatusChangeRepositoryInterface::class, AppointmentStatusChangeRepository::class);
        $this->app->bind(ItemRepositoryInterface::class, ItemRepository::class);
        $this->app->bind(PartRepositoryInterface::class, PartRepository::class);
        $this->app->bind(UserStockRepositoryInterface::class, UserStockRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(TechRepositoryInterface::class, TechRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(WarehouseInterface::class, WarehouseRepository::class);
        $this->app->bind(AddressRepositoryInterface::class, AddressRepository::class);
        $this->app->bind(WarehouseTransferRepositoryInterface::class, WarehouseTransferRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(EmergencyItemRepositoryInterface::class, EmergencyItemRepository::class);
        $this->app->bind(EmergencyItemConditionRepositoryInterface::class, EmergencyItemConditionRepository::class);
        $this->app->bind(UserLogRepositoryInterface::class, UserLogRepository::class);
        $this->app->bind(RolePermissionInterface::class, RolePermissionRepository::class);
        $this->app->bind(TransferOrderInterface::class, TransferOrderRepository::class);
        $this->app->bind(AppointmentChangeStatusRequestInterface::class, AppointmentChangeStatusRequestRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(CompleteFormRepositoryInterface::class, CompleteFormRepository::class);
        $this->app->bind(AppointmentFormSubmissionRepositoryInterface::class, AppointmentFormSubmissionRepository::class);
        $this->app->bind(ChangeRequestReasonInterface::class, ChangeRequestReasonRepository::class);
        $this->app->bind(AppointmentTransactionInterface::class, AppointmentTransactionRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->make(ChannelManager::class)->extend('fcm', function ($app) {
            return new FcmChannel();
        });
        RateLimiter::for('whatsapp', function () {
            return Limit::perMinute(150); // adjust based on WhatsApp API limits
        });

        // if (app()->environment('production')) {
        //     URL::forceScheme('https');
        // }
    }
}
