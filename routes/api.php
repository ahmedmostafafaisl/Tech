<?php

use App\Http\Controllers\Api\Address\AddressController;
use App\Http\Controllers\Api\Announcement\AnnouncementController;
use App\Http\Controllers\Api\AppointmentForm\AppointmentFormAdminController;
use App\Http\Controllers\Api\AppointmentForm\AppointmentFormController;
use App\Http\Controllers\Api\AppointmentTransaction\AppointmentTransactionController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\ChangeRequestReason\ChangeRequestController;
use App\Http\Controllers\Api\ChangeRequestReason\ChangeRequestReasonController;
use App\Http\Controllers\Api\Commands\CommandController;
use App\Http\Controllers\Api\CompleteForm\AppointmentFormSubmissionController;
use App\Http\Controllers\Api\CompleteForm\CompleteFormController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\DY365\DyController;
use App\Http\Controllers\Api\DY365\SchedulerController;
use App\Http\Controllers\Api\Evaluation\EvaluationMessageController;
use App\Http\Controllers\Api\Invoice\InvoiceController;
use App\Http\Controllers\Api\Invoice\ShortLinkController;
use App\Http\Controllers\Api\Lead\LeadController;
use App\Http\Controllers\Api\Lead\OrderController;
use App\Http\Controllers\Api\Logs\TechnicianAppointmentLogController;
use App\Http\Controllers\Api\Logs\TimeOutLogController;
use App\Http\Controllers\Api\NewDirectIntegrationController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\Payment\ClickPayController;
use App\Http\Controllers\Api\Payment\TabbyPaymentController;
use App\Http\Controllers\Api\Payment\TabbyWebhookController;
use App\Http\Controllers\Api\Payment\TamaraPaymentController;
use App\Http\Controllers\Api\Pdf\Invoice2Controller;
use App\Http\Controllers\Api\Service\ServiceController;
use App\Http\Controllers\Api\Settings\InvoiceSettingController;
use App\Http\Controllers\Api\Settings\SettingController;
use App\Http\Controllers\Api\System\SystemHealthController;
use App\Http\Controllers\Api\Tech\TechController;
use App\Http\Controllers\Api\Transfer\TransferOrderController;
use App\Http\Controllers\Api\Transfer\WarehouseTransferController;
use App\Http\Controllers\Api\User\RolesAndPermissions\RolePermissionController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\UserLogController;
use App\Http\Controllers\Api\User\UserStockController;
use App\Http\Controllers\Api\Version\AppVersionController;
use App\Http\Controllers\Api\Warehouse\WarehouseController;
use App\Http\Controllers\Api\WhatsApp\WhatsAppController;
use App\Http\Controllers\Appointment\AppointmentChangeStatusRequestController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Appointment\CompleteIssueController;
use App\Http\Controllers\Appointment\Emergency\EmergencyItemConditionController;
use App\Http\Controllers\Appointment\Emergency\EmergencyItemController;
use App\Http\Controllers\Appointment\FavoriteSalesOrderController;
use App\Http\Controllers\Item\ItemController;
use App\Http\Controllers\Part\PartController;
use App\Http\Controllers\Task\TaskController;
use App\Models\Appointment;
use App\Models\DirectAppointment;
use App\Models\PreAppointmentMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\LaravelPdf\Facades\Pdf;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    Route::get('/allEmployees', [UserController::class, 'allEmployees']);
    Route::get('/allTechs', [UserController::class, 'allTechs']);
    Route::get('/allCustomers', [UserController::class, 'allCustomers']);
    Route::get('/{id}', [UserController::class, 'find']);
    // Route::put('/{id}', [UserController::class, 'update']);
    // update user data
    Route::put('/{user}', [UserController::class, 'update']);
    // update user status
    Route::post('/{user}/status', [UserController::class, 'updateUserStatus']);

    Route::post('/', [UserController::class, 'store']);
    // single user appointments
    Route::get('/{id}/appointments', [UserController::class, 'singleUserAppointments']);
    // single user tasks
    Route::get('/{id}/tasks', [UserController::class, 'singleUserTasks']);

    // Route::post('/auth/register', [UserController::class, 'register']);
});
// Route::post('/auth/register', [UserController::class, 'register']);

Route::post('/auth/send-otp', [UserController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [UserController::class, 'verifyOtp']);
Route::post('/auth/verify-pin', [UserController::class, 'verifyPinCode']);
Route::post('/auth/login', [UserController::class, 'login']);

Route::post('auth/update/pin', [UserController::class, 'updatePinCode'])->middleware('auth:sanctum');
// Route to request pin reset

Route::post('auth/pin-reset/request', [UserController::class, 'requestPinReset']);

Route::middleware(['auth:api', 'can:update users'])->group(function () {
    Route::post('auth/pin-reset/approve', [UserController::class, 'approveResetRequest']);
});

//  Customers routes
Route::middleware('auth:sanctum')->prefix('customers')->group(function () {
    Route::get('/all', [CustomerController::class, 'index']);
    Route::get('/{id}', [CustomerController::class, 'show']);
});

// Technicians routes
Route::middleware('auth:sanctum')->prefix('technician')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::get('/all', [UserController::class, 'index']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::get('/get/stock', [TechController::class, 'getTechStock']);
    Route::get('/day/glance', [TechController::class, 'getDayAppointments']);
    Route::get('/day/home', [TechController::class, 'getTechHome']);
    Route::get('summary/appointments', [TechController::class, 'getSummaryBetweenDates']);
    Route::get('/today/tomorrow', [TechController::class, 'getTodayAndTomorrowSummary']);
    // sync tech functions
    Route::post('/sync/functions', [TechController::class, 'SyncTechFunctions']);
    // Update stock quantity for a specific item or part
    Route::post('/stock/update-quantity', [UserStockController::class, 'bulkUpdate']);
    // sync multiple items
    Route::post('/sync-items', [UserStockController::class, 'syncItems']);
});

// Appointments routes

Route::middleware('auth:sanctum')->prefix('appointments')->group(function () {
    Route::get('/all', [AppointmentController::class, 'index']);
    Route::post('/', [AppointmentController::class, 'store']);
    Route::get('/{id}', [AppointmentController::class, 'show']);
    Route::put('/{id}', [AppointmentController::class, 'update']);
    // Route::delete('/{id}', [AppointmentController::class, 'destroy']);
    Route::get('/technician/{id}', [AppointmentController::class, 'getTechnicianAppointments']);
    // update appointment status  to on way or on site or hold
    Route::post('rescheduleOrCancel/{id}', [AppointmentController::class, 'rescheduleOrCancelAppointment']);
    // get appointment Items,Parts
    Route::get('inventory/{id}', [AppointmentController::class, 'appointmentItemsAndParts']);
    Route::post('items/Parts/{id}', [AppointmentController::class, 'updateAppointmentItemsAndParts']);
    // complete appointment
    Route::patch('/{appointment}/complete', [AppointmentController::class, 'completeAppointment']);
    // appointment payment
    Route::post('/payments/{appointmentId}', [AppointmentController::class, 'appointmentPaymentStore']);
    // Route::post('/update/payments/{appointmentId}', [AppointmentController::class, 'appointmentPaymentUpdate']);
    Route::get('/get/payments/{appointmentId}', [AppointmentController::class, 'getAppointmentPayments']);
    Route::put('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
    // reschedule or cancel  appointment or complete appointment
    Route::get('/technicians/{technicianId}/appointments', [AppointmentController::class, 'getTechnicianAppointments']);
    // Filter appointments
    Route::get('dash/filter', [AppointmentController::class, 'filterAppointments']);
    // Search appointments
    Route::get('dash/search', [AppointmentController::class, 'search']);
    // create new periodic or emergency appointment
    Route::post('/new/instance/{appointmentId}', [AppointmentController::class, 'newInstanceAppointment']);
    // send otp to complete appointment
    Route::post('/send-otp/{id}', [AppointmentController::class, 'sendOtp']);
    Route::post('/verify-otp/{id}', [AppointmentController::class, 'verifyOtp']);

    // get appointment change status requests
    Route::apiResource('/change-status-requests', AppointmentChangeStatusRequestController::class);
    Route::get('my/requests', [AppointmentChangeStatusRequestController::class, 'myRequests']);

    // handle sales line
    Route::post('/handle-sales-line', [AppointmentController::class, 'handleSalesLine']);
    // get pending
    Route::get('tech/dy/pending', [AppointmentController::class, 'getPendingDyAppointments']);

    // get last appointment payment
    Route::get('/last-payment/{appointmentId}/{type}', [AppointmentController::class, 'getLastAppointmentPayment']);

    // apply discount to appointment
    Route::post('/{id}/apply-discount', [AppointmentController::class, 'applyDiscount']);

    // export processing appointments

    // sync Single appointment
    Route::get('/single/sync', [AppointmentController::class, 'syncSingleAppointment']);
    // delete appointment with sales lines
    Route::post('/delete/{id}', [AppointmentController::class, 'deleteAppointmentWithSalesLines']);
    Route::get('v2/nonCompleted/appointments', [AppointmentController::class, 'sendNonCompletedAppointmentsReminder']);
});
Route::get('appointments/export/processing', [AppointmentController::class, 'exportProcessingAppointments']);
Route::get('appointments/export/direct', [AppointmentController::class, 'exportDirectAppointments']);
Route::get('appointments/export/direct/specific', [AppointmentController::class, 'exportSpecificDirectAppointments']);

// warehouses routes
Route::middleware('auth:sanctum')->prefix('warehouses')->group(function () {
    Route::get('/', [WarehouseController::class, 'index']);
    Route::post('/', [WarehouseController::class, 'store']);
    Route::get('/{id}', [WarehouseController::class, 'show']);
    Route::put('/{id}', [WarehouseController::class, 'update']);
    Route::delete('/{id}', [WarehouseController::class, 'destroy']);
    // get technician warehouse stock
    Route::get('/tech/stock', [WarehouseController::class, 'getTechnicianWarehouses']);
});

// Categories routes
Route::middleware('auth:sanctum')->prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);
});

// Items routes
Route::middleware('auth:sanctum')->prefix('items')->group(function () {
    Route::post('/', [ItemController::class, 'store']);
    Route::put('/{id}', [ItemController::class, 'update']);
    Route::get('/', [ItemController::class, 'index']);
    Route::get('/{id}', [ItemController::class, 'show']);
    Route::get('history/{id}', [EmergencyItemController::class, 'getItemHistory']);
    // Route::get('client-items', [EmergencyItemController::class, 'getClientItems']);
    Route::get('/client/items/{customerId}', [EmergencyItemController::class, 'getClientItems']);
});

// Parts routes
Route::middleware('auth:sanctum')->prefix('parts')->group(function () {
    Route::post('/', [PartController::class, 'store']);
    Route::put('/{id}', [PartController::class, 'update']);
    Route::get('/', [PartController::class, 'index']);
    Route::get('/{id}', [PartController::class, 'show']);
});

// User stocks routes
Route::middleware('auth:sanctum')->prefix('user-stock')->group(function () {
    Route::get('/{userId}', [UserStockController::class, 'show']);
    Route::post('', [UserStockController::class, 'storeOrUpdate']);
    Route::delete('/{id}', [UserStockController::class, 'destroy']);
    Route::post('/add-items', [UserStockController::class, 'addItems']);
    Route::post('/add-parts', [UserStockController::class, 'addParts']);
    // Route::post('/add-parts', [UserStockController::class, 'addParts']);
    // Route::post('transfer/from/stock', [UserStockController::class, 'transferFromStock']);
    // Route::post('transfer/to/stock', [UserStockController::class, 'transferToStock']);
    // Route::post('transfer/from/technician', [UserStockController::class, 'transferFromTechnician']);
    // Route::post('transfer/to/technician', [UserStockController::class, 'transferToTechnician']);
});

// Task routes
Route::middleware('auth:sanctum')->prefix('tasks')->group(function () {
    Route::get('/', [TaskController::class, 'index']);
    Route::post('/', [TaskController::class, 'store']);
    Route::get('/{id}', [TaskController::class, 'show']);
    Route::put('/{id}', [TaskController::class, 'update']);
    Route::delete('/{id}', [TaskController::class, 'destroy']);
    Route::get('/technician/tasks', [TaskController::class, 'getTechnicianTasks']);
});

// Address routes
Route::middleware('auth:sanctum')->prefix('addresses')->group(function () {
    Route::get('/', [AddressController::class, 'index']);
    Route::post('/', [AddressController::class, 'store']);
    Route::get('/{id}', [AddressController::class, 'show']);
    Route::put('/{id}', [AddressController::class, 'update']);
    Route::delete('/{id}', [AddressController::class, 'destroy']);
});
Route::middleware('auth:sanctum')->get('/customer/addresses', [AddressController::class, 'getAuthCustomerAddresses']);
Route::middleware('auth:sanctum')->get('/customer/addresses/{id}', [AddressController::class, 'getCustomerAddresses']);

// transfers
Route::middleware('auth:sanctum')->prefix('warehouse-transfers')->group(function () {
    Route::get('/', [WarehouseTransferController::class, 'index']);
    Route::post('/', [WarehouseTransferController::class, 'store']);
    Route::get('/{id}', [WarehouseTransferController::class, 'show']);
    Route::put('/{id}', [WarehouseTransferController::class, 'update']);
    Route::delete('/{id}', [WarehouseTransferController::class, 'destroy']);
    Route::get('/tech/warehouse-transfers', [WarehouseTransferController::class, 'getMyRequests']);
});

// announcements
Route::middleware('auth:sanctum')->prefix('announcements')->group(function () {
    Route::get('/', [AnnouncementController::class, 'index']);
    Route::post('/', [AnnouncementController::class, 'store']);
    Route::get('/{id}', [AnnouncementController::class, 'show']);
    Route::put('/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/{id}', [AnnouncementController::class, 'destroy']);
});

// Invoice routes
Route::middleware('auth:sanctum')->prefix('invoices')->group(function () {
    Route::get('appointment/{id}', [DyController::class, 'getOrCreateInvoice']);

    Route::get('/', [InvoiceController::class, 'index']);
    // Route::post('/', [InvoiceController::class, 'store']);
    Route::get('/{id}', [InvoiceController::class, 'show']);
    // Route::put('/{id}', [InvoiceController::class, 'update']);
    // Route::delete('/{id}', [InvoiceController::class, 'destroy']);
    Route::get('appointment/{appointment}', [InvoiceController::class, 'getByAppointment']);
});

// Emergency Appointment  items
Route::middleware('auth:sanctum')->prefix('emergency')->group(function () {
    Route::get('items/{id}', [EmergencyItemController::class, 'appointmentItems']);
    Route::post('items/{id}', [EmergencyItemController::class, 'store']);
    Route::put('items/{id}', [EmergencyItemController::class, 'update']);
    Route::get('item/{id}', [EmergencyItemController::class, 'show']);
    Route::post('/appointment/items/attach', [EmergencyItemController::class, 'attachItemsWithParts']);
    Route::post('/appointment/items/{item}', [EmergencyItemController::class, 'deleteItemWithParts']);
    Route::post('/appointment/items/{item}/parts', [EmergencyItemController::class, 'attachParts']);
    // Emergency Item Conditions
    Route::post('/item-conditions', [EmergencyItemConditionController::class, 'store']);
    Route::put('/item-conditions/{id}', [EmergencyItemConditionController::class, 'update']);
    Route::get('/item-conditions/{mainItemId}', [EmergencyItemConditionController::class, 'showByMainItem']);
    Route::get('/item-conditions/appointment/{appointmentId}', [EmergencyItemConditionController::class, 'getByAppointment']);
    // Delete conditions by main item
    Route::delete('/item/conditions', [EmergencyItemConditionController::class, 'destroyByMainItem']);
});

Route::apiResource('user-logs', UserLogController::class);
Route::get('user-logs/auth/user', [UserLogController::class, 'getAuthUserLogs'])->middleware('auth:sanctum');

// sync custom warehouse stock  by item number
Route::get('dy365/sync-warehouses/stock/', [WarehouseController::class, 'syncCustomWarehouseStock']);
// Sync custom technician stock
Route::get('dy365/sync-technician/stock/{id}', [UserStockController::class, 'syncCustomTechnicianStock']);
// dy365 routes
Route::middleware('auth:sanctum')->prefix('dy365')->group(function () {
    Route::get('/warehouses', [DyController::class, 'getWarehouses']);
    Route::get('/product-categories', [DyController::class, 'getProductCategories']);
    Route::get('/payment-methods', [DyController::class, 'getPaymentMethods']);
    Route::get('/technicians', [DyController::class, 'getTechnicians']);
    Route::get('/customers', [DyController::class, 'getCustomers']);
    Route::get('/tech/stock', [DyController::class, 'getTechnicianStock']);
    Route::get('/tech/transfers', [DyController::class, 'getTechnicianTransfers']);
    Route::get('/appointments', [DyController::class, 'getAppointments']);
    Route::get('/tech/appointments', [DyController::class, 'getTechnicianAppointments']);
    Route::get('/warehouses/stock', [DyController::class, 'getWarehouseStock']);
    Route::get('/tech/change-status-requests', [DyController::class, 'getTechnicianChangeStatusRequests']);
    // Add sales line to appointment
    Route::post('/appointments/add-sales-line', [DyController::class, 'addSalesLine']);
    // Update sales line in appointment
    Route::post('/appointments/update-sales-line', [DyController::class, 'updateSalesLine']);
    // delete appointment sales lines
    Route::post('/appointments/delete-sales-line', [DyController::class, 'deleteSalesLine']);
    // Sync categories and warehouses
    Route::get('/sync-categories', [CategoryController::class, 'syncCategories']);
    Route::get('/sync-warehouses', [WarehouseController::class, 'syncWarehouses']);
    // sync custom warehouse stock
    // Route::get('/sync-warehouses/stock/{id}', [WarehouseController::class, 'syncCustomWarehouseStock']);
    // Sync technicians stocks
    Route::get('/sync-technician/stock', [UserStockController::class, 'syncTechnicianStock']);

    // Sync technicians transfers
    Route::get('/sync/technician/transfers', [TransferOrderController::class, 'syncAllTechnicianTransfers']);
    // Sync custom technician transfers
    Route::get('/sync/technician/transfers/{id}', [TransferOrderController::class, 'syncCustomTechnicianTransfers']);
    // Sync appointments
    Route::get('/sync/appointments', [AppointmentController::class, 'syncAllAppointments']);
    // Sync all technicians appointments
    Route::get('/sync/technician/appointments', [AppointmentController::class, 'syncAllTechnicianAppointments']);
    // Sync appointment by sales order
    Route::get('/sync/appointments/sales-order/{id}', [AppointmentController::class, 'getAppointmentBySalesOrder']);
    // Sync warehouses stock
    Route::get('/sync/warehouses/stock', [WarehouseController::class, 'syncWarehouseStock']);
    // sync tech transfers
    Route::get('/sync/technician/transfers', [TransferOrderController::class, 'syncAllTechnicianTransfers']);

    // Sync all technicians change status requests
    Route::get('/sync/technician/change-status-requests', [AppointmentChangeStatusRequestController::class, 'syncAllTechnicianChangeStatusRequests']);
    // complete success payments
    Route::get('/sync/complete-success-payments', [DyController::class, 'completeSuccessPayments']);
    // get sales history
    Route::get('/sales-history/{id}', [DyController::class, 'getSalesHistory']);

    // DY365 Payment
});
Route::post('dy365/payments/links', [DyController::class, 'getPaymentLinks']);

Route::get('/tabby/success/reference_id={reference_id}/payment_method={payment_method}', [DyController::class, 'success'])->name('dy.tabby.success');
Route::get('/tabby/cancel/reference_id={reference_id}/payment_method={payment_method}', [DyController::class, 'cancel'])->name('dy.tabby.cancel');
Route::get('/tabby/failure/reference_id={reference_id}/payment_method={payment_method}', [DyController::class, 'failure'])->name('dy.tabby.failure');
Route::post('/tabby/webhook/reference_id={reference_id}/payment_method={payment_method}', [DyController::class, 'tamaraWebhook'])->name('dy.tabby.webhook');

// get payment status

// routes/api.php or web.php
Route::post('get/payment/status', [DyController::class, 'getPaymentStatus'])->name('dy.payment.status');
// Roles and Permissions routes
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    // Roles
    Route::get('roles', [RolePermissionController::class, 'roles']);
    Route::post('roles', [RolePermissionController::class, 'storeRole']);
    Route::put('roles/{id}', [RolePermissionController::class, 'updateRole']);
    Route::get('roles/{id}', [RolePermissionController::class, 'showRole']);
    Route::delete('roles/{id}', [RolePermissionController::class, 'deleteRole']);
    Route::get('/roles/{role}/users', [RolePermissionController::class, 'usersByRole']);

    // Permissions
    Route::get('permissions', [RolePermissionController::class, 'permissions']);
    Route::post('permissions', [RolePermissionController::class, 'storePermission']);
    Route::put('permissions/{id}', [RolePermissionController::class, 'updatePermission']);
    Route::delete('permissions/{id}', [RolePermissionController::class, 'deletePermission']);
    // inventory
    Route::get('inventory/all', [WarehouseController::class, 'getInventory']);
});
// payment routes
Route::middleware('auth:sanctum')->prefix('payments')->group(function () {
    Route::get('/tabby/checkout', [TabbyPaymentController::class, 'createCheckout'])->name('tabby.checkout');
    Route::post('tabby/session/status', [TabbyPaymentController::class, 'getPaymentStatus']);
});
Route::get('/tabby/success', [TabbyPaymentController::class, 'success'])->name('tabby.success');
Route::get('/tabby/cancel', [TabbyPaymentController::class, 'cancel'])->name('tabby.cancel');
Route::get('/tabby/failure', [TabbyPaymentController::class, 'failure'])->name('tabby.failure');
// tamara payment routes
Route::get('/tamara/success', [TamaraPaymentController::class, 'success'])->name('tamara.success');
Route::get('/tamara/cancel', [TamaraPaymentController::class, 'cancel'])->name('tamara.cancel');
Route::get('/tamara/failure', [TamaraPaymentController::class, 'failure'])->name('tamara.failure');
Route::post('/tamara/webhook', [TamaraPaymentController::class, 'handle'])->name('tamara.webhook');
// ClickPay payment routes
// NOTE: /create initiates a real charge and /refund moves real money — both require
// an authenticated staff/technician session. Return + callback endpoints stay public
// because ClickPay redirects/POSTs to them without an app auth token.
Route::prefix('clickpay')->group(function () {
    Route::post('/callback', [ClickPayController::class, 'handleCallback'])->name('clickpay.callback');
    Route::match(['get', 'post'], '/return', [ClickPayController::class, 'handleReturn'])->name('clickpay.return');
    Route::match(['get', 'post'], '/return-dy', [ClickPayController::class, 'dyHandleReturn'])->name('dy.clickpay.return');
});

// Route::prefix('clickpay')->group(function () {
//     Route::post('/callback', [ClickPayController::class, 'handleCallback'])->name('clickpay.callback');
//     Route::get('/return', [ClickPayController::class, 'handleReturn'])->name('clickpay.return');
//     Route::get('/return', [ClickPayController::class, 'dyHandleReturn'])->name('dy.clickpay.return');
// });
// notifications routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [TechController::class, 'index']);
    Route::post('/notifications/mark-all-read', [TechController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/mark-read', [TechController::class, 'markAsRead']);
    Route::post('tech/update-fcm-token', [TechController::class, 'updateFcmToken']);
});
// transfer orders
Route::middleware('auth:sanctum')->prefix('transfer-orders')->group(function () {
    Route::get('/', [TransferOrderController::class, 'index']);
    Route::post('/', [TransferOrderController::class, 'store']);
    Route::get('/{id}', [TransferOrderController::class, 'show']);
    Route::put('/{id}', [TransferOrderController::class, 'update']);
    Route::delete('/{id}', [TransferOrderController::class, 'destroy']);
    Route::get('/tech/transfer', [TransferOrderController::class, 'newTechnicianTransfers']);
});
// scheduler
Route::middleware(['auth:sanctum'])->get('/run-scheduler', [SchedulerController::class, 'runAll']);

// commands
Route::middleware(['auth:sanctum'])->prefix('run')->group(function () {
    Route::post('/sync-warehouses', [CommandController::class, 'syncWarehouses']);
    Route::post('/sync-categories', [CommandController::class, 'syncCategories']);
    Route::post('/sync-technicians', [CommandController::class, 'syncTechnicians']);
    Route::post('/sync-stock', [CommandController::class, 'syncStock']);
    Route::post('/sync-warehouse-stock', [CommandController::class, 'syncWarehouseStock']);
    Route::post('/sync-technician-transfers', [CommandController::class, 'syncTechnicianTransfers']);
    Route::post('/sync-technician-appointments', [CommandController::class, 'syncTechnicianAppointments']);
    Route::post('/sync-all-appointments', [CommandController::class, 'syncAllAppointments']);
    Route::post('/sync-technician-status-requests', [CommandController::class, 'syncTechnicianStatusRequests']);
    Route::post('/sync-customers', [CommandController::class, 'syncCustomers']);
});
// WhatsApp notifications
Route::post('WhatsApp/notify', [WhatsAppController::class, 'notify']);
Route::post('WhatsApp/sendPreAppointmentMessage', [WhatsAppController::class, 'sendPreAppointmentMessage']);
// Get WhatsApp templates
Route::get('WhatsApp/templates', [WhatsAppController::class, 'templates']);
// receive WhatsApp messages // webhook
Route::match(['get', 'post'], '/WhatsApp/receive', [WhatsAppController::class, 'receive']);
// Route::match(['get', 'post'], '/WhatsApp/receive', [WhatsAppController::class, 'receive']);
// Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verifyWebhook']);
// Route::post('/webhook/whatsapp', [WhatsAppController::class, 'handleWebhook']);

Route::post('/whatsapp/send', [WhatsAppController::class, 'sendMessage']);

// WhatsApp PDF link
Route::post('/appointments/{appointmentId}/send-invoice', [DyController::class, 'sendInvoice']);

Route::post('/services/import', [ServiceController::class, 'import'])->name('services.import');
Route::get('/services', [ServiceController::class,  'index'])->name('services.index');
Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
Route::post('/services/{id}', [ServiceController::class, 'show'])->name('services.show');
Route::post('/services/{id}', [ServiceController::class, 'update'])->name('services.update');
Route::post('/services/{id}/delete', [ServiceController::class, 'delete'])->name('services.delete');

//  fix appointments data
Route::post('/fix-appointments-data', [NewDirectIntegrationController::class, 'fixAppointments']);
// test complete v2
Route::post('/test-complete-v2', [NewDirectIntegrationController::class, 'testCompleteV2'])->name('test.complete.v2');

Route::post('/run-appointments-reminder', [NewDirectIntegrationController::class, 'run_reminder']);

Route::get('/empty-jobs', function () {
    DB::table('jobs')->truncate();

    return '✅ Jobs table emptied successfully.';
});

Route::get('/sync-single-warehouse/{warehouseId}', function ($warehouseId) {
    $cmd = 'php ' . base_path('artisan') . ' sync:single-warehouse-stock ' . escapeshellarg($warehouseId) . ' > /dev/null 2>&1 &';
    exec($cmd);

    return response()->json(['message' => "✅ Background sync started for {$warehouseId}"]);
});

Route::get('/appointments/today/completed', function () {
    $today = Carbon::today();
    // $today = now()->format('Y-m-d');
    $appointments = DirectAppointment::whereDate('appointment_date', $today)
        ->with('payments')
        ->get();

    return response()->json([
        'date' => $today->toDateString(),
        'count' => $appointments->count(),
        'appointments' => $appointments,
    ]);
});

Route::get('pre/appointments', function () {

    $appointments = PreAppointmentMessage::all();

    return response()->json([
        'count' => $appointments->count(),
        'appointments' => $appointments,
    ]);
});

Route::get('/appointments/all/completed', function (Request $request) {
    $startDate = '2025-10-10';
    $endDate = now()->format('Y-m-d');

    // Get pagination parameters (default: page=1, per_page=10)
    $page = $request->get('page', 1);
    $perPage = $request->get('per_page', 10);

    $appointments = Appointment::whereIn('status', ['Completed', 'complete', 'processing'])
        ->whereBetween(DB::raw('DATE(appointment_date)'), [$startDate, $endDate])
        ->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'current_page' => $appointments->currentPage(),
        'per_page' => $appointments->perPage(),
        'total' => $appointments->total(),
        'last_page' => $appointments->lastPage(),
        'appointments' => $appointments->items(),
    ]);
});

Route::get('flag/appointments/true', function (Request $request) {
    $sales_order_id = $request->input('sales_order_id');
    $appointments = Appointment::where('sales_order_id', $sales_order_id)->first();
    $appointments->v2_flag = true;
    $appointments->save();

    return response()->json([
        'date' => $appointments,
        'message' => 'Flag set to true',
    ]);
});

Route::get('specific/appointments', function (Request $request) {
    return $appointments = DirectAppointment::whereDate('created_at', $request->date)
        ->with('payments')
        ->get();
});

Route::get('logout/users', function (Request $request) {
    PersonalAccessToken::query()->delete();
});

Route::get('/appointments/date/completed', function (Request $request) {
    $date = $request->input('date');
    $appointments = DirectAppointment::whereIn('status', ['Completed', 'complete', 'processing'])
        ->whereDate('created_at', $date)
        ->with('payments')
        ->get();

    return response()->json([
        'date' => $date,
        'count' => $appointments->count(),
        'appointments' => $appointments,
    ]);
});

Route::middleware('auth:sanctum')->prefix('new')->group(function () {
    Route::post('/integration/today-appointments', [NewDirectIntegrationController::class, 'todayAppointments']);
    Route::get('/integration/single-appointment/{sales_order_id}', [NewDirectIntegrationController::class, 'singleAppointment']);
    Route::get('/integration/single2-appointment/{sales_order_id}', [NewDirectIntegrationController::class, 'singleAppointment2']);
    Route::get('/integration/single3-appointment/{book_id}', [NewDirectIntegrationController::class, 'singleAppointmentByBookId']);
    Route::post('/integration/tech-warehouse', [NewDirectIntegrationController::class, 'techWarehouse']);
    Route::post('/integration/main-warehouse', [NewDirectIntegrationController::class, 'mainWarehouse']);
    // product limit
    Route::post('/integration/product/limit', [NewDirectIntegrationController::class, 'getTechnicianLimitData']);
    Route::post('/integration/add-sales-line', [NewDirectIntegrationController::class, 'addSalesLine']);
    Route::post('/integration/update-sales-line', [NewDirectIntegrationController::class, 'updateSalesLine']);
    Route::post('/integration/delete-sales-line', [NewDirectIntegrationController::class, 'deleteSalesLine']);
    Route::post('/integration/get-or-update-invoice/{sales_order_id}', [NewDirectIntegrationController::class, 'getOrCreateInvoice']);
    Route::post('/integration/get-or-update-invoice-by-book-id/{book_id}', [NewDirectIntegrationController::class, 'getOrCreateInvoiceByBookId']);

    Route::post('/integration/get-transfer-orders/{tech_id}', [NewDirectIntegrationController::class, 'getTransferOrders']);
    Route::post('/integration/create-transfer-order', [NewDirectIntegrationController::class, 'createTransferOrder']);
    Route::post('/integration/update-transfer-order', [NewDirectIntegrationController::class, 'updateTransferOrderStatus']);
    Route::post('/integration/delete-transfer-order', [NewDirectIntegrationController::class, 'deleteTransferOrder']);
    // new transfer order routes
    // used
    Route::post('/integration/new-transfer-order', [TransferOrderController::class, 'newStore']);
    // used
    Route::post('/integration/new/update-transfer-order-items', [TransferOrderController::class, 'newUpdate']);
    // used
    Route::post('/integration/new/delete-transfer-order', [TransferOrderController::class, 'newDestroy']);
    // used
    Route::post('/integration/new/transfer-orders/{tech_id}', [TransferOrderController::class, 'getTransferOrders']);

    // used
    Route::get('/integration/new/single/transfer-orders/{tech_id}/{transfer_order_id}', [TransferOrderController::class, 'getSingleTransferOrders']);

    // send payment links
    Route::post('/integration/send-payment-links', [NewDirectIntegrationController::class, 'sendPaymentLinks']);
    // check sales order id payment status or reference id payment status
    Route::post('/integration/check-payment-status', [NewDirectIntegrationController::class, 'checkPaymentStatus']);
    // completeAppointment
    Route::post('/integration/complete-appointment', [NewDirectIntegrationController::class, 'completeAppointment']);
    // get invoice by appointment
    Route::post('/integration/get-invoice-by-appointment/{sales_order_id}', [NewDirectIntegrationController::class, 'getInvoiceByAppointment']);
    // get single direct appointment
    Route::post('/integration/get/direct-appointment/{sales_order_id}', [NewDirectIntegrationController::class, 'getSingleDirectAppointment']);
    Route::post('/integration/get/direct-appointment/book_id/{book_id}', [NewDirectIntegrationController::class, 'getSingleDirectAppointment2']);

    Route::post('/integration/single/direct-appointment/{book_id}', [NewDirectIntegrationController::class, 'getSingleDirectAppointmentPayments']);

    // tech change status requests
    Route::post('/integration/tech/change-status-requests/{tech_id}', [NewDirectIntegrationController::class, 'getChangeRequests']);
    // create change status request
    Route::post('/integration/tech/create/change-status-request', [NewDirectIntegrationController::class, 'submitChangeRequest']);
    // update appointment attachments
    Route::post('/integration/appointment/store-attachments', [NewDirectIntegrationController::class, 'storeAttachments']);
    // check complete eligibility
    Route::post('/integration/check-complete-eligibility/{book_id}', [NewDirectIntegrationController::class, 'checkTodayDirectAppointmentsCompleted']);
    // get tech distributions
    Route::post('/integration/distributions/{tech_id}', [NewDirectIntegrationController::class, 'getTechnicianDistributions']);
});
Route::post('new/integration/get-transfer-order/{tech_id}/{transfer_order_id}', [NewDirectIntegrationController::class, 'getSingleTransferOrder']);
// get single Technician stock
Route::post('new/integration/get-tech/{tech_id}', [NewDirectIntegrationController::class, 'getSingleTechnician']);
// return routes for new tabby integration
Route::get('/integration/tabby/success/reference_id={reference_id}/sales_order_id={sales_order_id}', [TabbyPaymentController::class, 'newSuccess'])->name('new.tabby.success');
Route::get('/integration/tabby/cancel/reference_id={reference_id}/sales_order_id={sales_order_id}', [TabbyPaymentController::class, 'newCancel'])->name('new.tabby.cancel');
Route::get('/integration/tabby/failure/reference_id={reference_id}/sales_order_id={sales_order_id}', [TabbyPaymentController::class, 'newFailure'])->name('new.tabby.failure');

// tabby webhook
Route::post('/webhooks/tabby', [TabbyWebhookController::class, 'handle'])
    ->name('tabby.webhook');
// GET /integration/tabby/sync-payment-status?payment_id=...
Route::get('integration/tabby/sync-payment-status', [TabbyPaymentController::class, 'syncPaymentStatus']);
// sync tamara payment
Route::get('integration/tamara/sync-payment-status', [TamaraPaymentController::class, 'syncPaymentStatus']);
// get   tabby payment details :
Route::get('integration/tabby/payment-details', [TabbyPaymentController::class, 'getPaymentDetails']);
// get tamara Payment Details
Route::get('integration/tamara/payment-details', [TamaraPaymentController::class, 'getPaymentDetails']);

// get clickPay payment details
Route::get('integration/clickpay/payment-details', [ClickPayController::class, 'getPaymentDetails']);

Route::get('integration/clickpay/sync-payment-status', [ClickPayController::class, 'syncPaymentStatus']);

// return routes for new tamara integration
Route::get('/integration/tamara/success/reference_id={reference_id}/sales_order_id={sales_order_id}', [TamaraPaymentController::class, 'newSuccess'])->name('new.tamara.success');
Route::get('/integration/tamara/cancel/reference_id={reference_id}/sales_order_id={sales_order_id}', [TamaraPaymentController::class, 'newCancel'])->name('new.tamara.cancel');
Route::get('/integration/tamara/failure/reference_id={reference_id}/sales_order_id={sales_order_id}', [TamaraPaymentController::class, 'newFailure'])->name('new.tamara.failure');
Route::post('/integration/tamara/notification/reference_id={reference_id}/sales_order_id={sales_order_id}', [TamaraPaymentController::class, 'newNotification'])->name('new.tamara.notification');

// ClickPay new integration
Route::post('/integration/clickpay/success/reference_id={reference_id}/sales_order_id={sales_order_id}', [ClickPayController::class, 'newRefund'])->name('new.clickpay.refund');
Route::post('/integration/clickpay/callback', [ClickPayController::class, 'newHandleCallback'])->name('new.clickpay.callback');
Route::match(['get', 'post'], '/integration/clickpay/return', [ClickPayController::class, 'newHandleReturn'])->name('new.clickpay.return');

Route::get('/invoice', function (Request $request) {
    return view('Invoice.invoice');
});

Route::get('/integration/get-or-update-invoice/{sales_order_id}', [NewDirectIntegrationController::class, 'generateInvoicePdf']);

Route::post('/check-version', [AppVersionController::class, 'check']);
Route::post('/app-version/update', [AppVersionController::class, 'updateAppVersion']);

// getAmountDifferences
Route::post('/getAmountDifferences', [NewDirectIntegrationController::class, 'getAmountDifferences']);

// export
Route::get('power-bi-messages', [DashboardController::class, 'getAllPreMessagesPowerBiOld']);

// Dashboard routes
Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {
    Route::get('/techs', [DashboardController::class, 'getAllTechnicians']);
    Route::get('/tech/{id}', [DashboardController::class, 'getSingleTechnician']);
    Route::get('/direct-appointment/{id}', [DashboardController::class, 'getSingleDirectAppointment']);
    Route::get('/direct-appointments', [DashboardController::class, 'getDirectAppointments']);
    Route::get('/technicians/{techId}/logs', [DashboardController::class, 'getTechnicianLogs']);
    Route::get('/technicians/{techId}/direct-appointments', [DashboardController::class, 'getTechnicianDirectAppointments']);
    Route::post('/direct-appointments/send-completion', [DashboardController::class, 'sendForCompletion']);
    Route::post('/direct-appointments/send-dy-reminders', [DashboardController::class, 'sendDyReminders']);
    // 🔹 Get all pre appointment messages (with filters + pagination + counts)
    Route::get('pre-appointment-messages', [DashboardController::class, 'getAllPreMessages']);
    // export
    Route::get('pre-messages/export', [DashboardController::class, 'export']);

    // 🔹 Get single pre appointment message by ID
    Route::get('pre-appointment-messages/{id}', [DashboardController::class, 'getPreMessageById']);
    // 🔹 Update pre appointment message status
    Route::post('/sendCustomerResponse', [DashboardController::class, 'sendCustomerResponse']);
    //
    Route::post('/sendCustomerResponse/array', [DashboardController::class, 'sendCustomerResponsesToDy']);
    // sync Technicians
    Route::post('/sync-technicians', [DashboardController::class, 'syncTechnicians']);

    // get all order lead  messages (with filters + pagination + counts)
    Route::resource('leads', LeadController::class)->only(['index', 'show']);

    // send evaluation
    Route::post('send/evaluation/{phone}/{orderType}/{bookId}', [EvaluationMessageController::class, 'createAndSendEvaluation']);

    Route::apiResource('evaluation-messages', EvaluationMessageController::class)->only(['index', 'show']);
    Route::get('evaluation-messages/phone/{phone}', [EvaluationMessageController::class, 'getByPhone']);
});

Route::middleware('auth:sanctum')->prefix('timeout-logs')->group(function () {
    Route::get('/', [TimeOutLogController::class, 'index']);
    Route::post('/update', [TimeOutLogController::class, 'update']);
    Route::post('/', [TimeOutLogController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/favorites/sales-orders', [FavoriteSalesOrderController::class, 'getFavorites']);
    Route::post('/favorites/sales-orders/{sales_order_id}', [FavoriteSalesOrderController::class, 'addFavorite']);
    Route::delete('/favorites/sales-orders/{sales_order_id}', [FavoriteSalesOrderController::class, 'deleteFavorite']);
});

Route::post('/favorites/{sales_order_id}/toggle', [FavoriteSalesOrderController::class, 'toggleFavorite'])
    ->middleware('auth:sanctum');

Route::get('/complete-issues', [CompleteIssueController::class, 'index']);
Route::get('/complete-issues/{sales_order_id}', [CompleteIssueController::class, 'showBySalesOrder']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/notifications/send', [NotificationController::class, 'send']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/unread', [NotificationController::class, 'unread']); // only unread
});
Route::middleware('auth:sanctum')->post('/notifications/tech/send', [NotificationController::class, 'sendTechNotification']);

// single appointment  BY BOOK ID
Route::get('/appointments/book/{book_id}', [NewDirectIntegrationController::class, 'refSingleAppointmentByBookId']);
// tamara import
Route::middleware('auth:sanctum')->post('/tamara/import', [TamaraPaymentController::class, 'import'])->name('tamara.import');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('complete-forms', CompleteFormController::class);
});

//  get tamara payment
Route::get(
    'tamara/orders/{orderId}/status',
    [TamaraPaymentController::class, 'getOrderStatus']
);

// new complete form routes
Route::prefix('v1/appointment-forms')->group(function () {
    Route::get('/types', [AppointmentFormController::class, 'types']);

    // root options by appointment type (services provided OR devices)
    Route::get('/types/{typeCode}/options', [AppointmentFormController::class, 'rootOptions']);

    // next step:
    // - periodic/service -> fields
    // - emergency -> problems (children)
    Route::get('/options/{optionId}/next', [AppointmentFormController::class, 'next']);

    // emergency only:
    // optionId here is problemId -> returns solutions
    Route::get('/options/{optionId}/children', [AppointmentFormController::class, 'children']);

    // final fields for any option (for emergency: solutionId)
    Route::get('/options/{optionId}/fields', [AppointmentFormController::class, 'fields']);
});

Route::middleware('auth:sanctum')
    ->prefix('v1/appointment-forms-admin')
    ->group(function () {
        // Create / Update Types
        Route::post('/types', [AppointmentFormAdminController::class, 'storeType']);
        // Add root option (service provided / device)
        Route::post('/types/{typeCode}/options', [AppointmentFormAdminController::class, 'storeRootOption']);
        // Add child option (problem / solution)
        Route::post('/options/{parentId}/children', [AppointmentFormAdminController::class, 'storeChildOption']);
        // Set required fields for option
        Route::put('/options/{optionId}/fields', [AppointmentFormAdminController::class, 'setFields']);
    });

Route::middleware('auth:sanctum')
    ->prefix('v1/appointment-forms-admin/bulk')
    ->group(function () {

        // 1) periodic/service: add options + fields
        Route::post('/{typeCode}/options-with-fields', [AppointmentFormAdminController::class, 'storeOptionsWithFields']);

        // 2) emergency: add devices -> problems -> solutions -> fields
        Route::post('/emergency/tree-with-fields', [AppointmentFormAdminController::class, 'storeEmergencyTreeWithFields']);
    });

// for add new type
Route::middleware('auth:sanctum')
    ->prefix('v1/appointment-forms-admin')
    ->group(function () {
        Route::post('/types/full', [AppointmentFormAdminController::class, 'storeTypeWithData']);
    });

// submit complete form
Route::middleware('auth:sanctum')
    ->prefix('v1/appointment-forms')
    ->group(function () {
        Route::post('/submit', [AppointmentFormSubmissionController::class, 'submit']);
    });

// get submission with values and fields for specific sales order or book id
Route::middleware('auth:sanctum')
    ->prefix('v1/appointment-forms')
    ->group(function () {
        Route::get('/submission/sales-order', [AppointmentFormSubmissionController::class, 'getBySalesOrderOrBook']);
    });

Route::get('/health', fn() => response()->json(['status' => 'ok'], 200));

// Appointment Logs

Route::middleware('auth:sanctum')->group(function () {
    // change request reasons
    Route::get('/change-request-reasons', [NewDirectIntegrationController::class, 'changeRequestReasons']);
    // change customer name
    Route::post('/change-customer-name', [NewDirectIntegrationController::class, 'changeCustomerName']);
    // add registration number to
    Route::post('/add-registration-number', [NewDirectIntegrationController::class, 'addRegistrationNumber']);
    Route::get('/technician-appointment-logs', [TechnicianAppointmentLogController::class, 'index']);
    Route::get('/technician-appointment-logs/book/{book_id}', [TechnicianAppointmentLogController::class, 'byBookId']);
    // resend attachment
    Route::post('/appointments/resend-attachments', [CompleteFormController::class, 'resendAttachmentsToDynamicsByBookId']);
});

// appointment cooldown  timer

Route::middleware('auth:sanctum')->prefix('settings')->group(function () {
    Route::get('/appointment-cooldown', [SettingController::class, 'index']);
    Route::put('/appointment-cooldown', [SettingController::class, 'updateCooldown']);
    // Tech
    Route::get('/transfer-orders/technician-to-technician/status', [SettingController::class, 'technicianToTechnicianStatus']);
    Route::put('/transfer-orders/technician-to-technician/status', [SettingController::class, 'updateTechnicianToTechnicianStatus']);
});

// invoice settings
Route::get('/invoice-settings', [InvoiceSettingController::class, 'index']);
Route::get('/invoice-settings/{key}', [InvoiceSettingController::class, 'show']);
Route::middleware('auth:sanctum')->put('/invoice-settings/{key}', [InvoiceSettingController::class, 'update']);

// invoice PDF routes
Route::get('/invoice/{id}/view', [Invoice2Controller::class, 'stream'])
    ->name('invoice.view');

Route::get('/invoice2/{id}/view', [Invoice2Controller::class, 'stream'])
    ->name('invoice2.view');

Route::get('/invoice/{id}/download', [Invoice2Controller::class, 'download'])
    ->name('invoice.download');

Route::post('/invoice/send-link', [InvoiceController::class, 'sendInvoiceLink'])
    ->name('invoice.send-link');

Route::get('invoice/debug/{id}', [App\Http\Controllers\Api\Pdf\InvoiceController::class, 'debug']);
Route::get('invoice/debug-chrome', [Invoice2Controller::class, 'debugChrome']);

// change status reasons
Route::middleware('auth:sanctum')->prefix('change-request-reasons')->group(function () {
    Route::get('/', [ChangeRequestReasonController::class, 'index']);
    Route::get('/{id}', [ChangeRequestReasonController::class, 'show']);
    Route::post('/', [ChangeRequestReasonController::class, 'store']);
    Route::put('/{id}', [ChangeRequestReasonController::class, 'update']);
    // Route::delete('/{id}', [ChangeRequestReasonController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('appointment-transactions')->group(function () {

    // ===== Transactions =====
    Route::get('/', [AppointmentTransactionController::class, 'index']);
    Route::post('/', [AppointmentTransactionController::class, 'store']);
    Route::get('/{id}', [AppointmentTransactionController::class, 'show']);
    Route::put('/{id}', [AppointmentTransactionController::class, 'update']);
    Route::delete('/{id}', [AppointmentTransactionController::class, 'destroy']);

    // ===== Lines =====
    Route::get('/{transactionId}/lines', [AppointmentTransactionController::class, 'indexLines']);
    Route::post('/{transactionId}/lines', [AppointmentTransactionController::class, 'storeLine']);
    Route::get('/lines/{lineId}', [AppointmentTransactionController::class, 'showLine']);
    Route::put('/lines/{lineId}', [AppointmentTransactionController::class, 'updateLine']);
    Route::delete('/lines/{lineId}', [AppointmentTransactionController::class, 'destroyLine']);

    // ===== Serials =====
    Route::get('/lines/{lineId}/serials', [AppointmentTransactionController::class, 'indexSerials']);
    Route::post('/lines/{lineId}/serials', [AppointmentTransactionController::class, 'storeSerial']);
    Route::get('/serials/{serialId}', [AppointmentTransactionController::class, 'showSerial']);
    Route::put('/serials/{serialId}', [AppointmentTransactionController::class, 'updateSerial']);
    Route::delete('/serials/{serialId}', [AppointmentTransactionController::class, 'destroySerial']);
});

// get products and bundle products
Route::middleware('auth:sanctum')->prefix('dy365')->group(function () {
    Route::post('/products', [DyController::class, 'getProducts']);
    Route::post('/bundle-products', [DyController::class, 'getBundleProducts']);
    Route::post('/add-bundle-product/appointment', [DyController::class, 'addBundleProductsToAppointment']);
});
// get all short links for invoices
Route::middleware('auth:sanctum')->get('/short-links', [ShortLinkController::class, 'index']);

// get change status request by book id
Route::get('/change-status-request/book/{book_id}', [NewDirectIntegrationController::class, 'getChangeRequestsByBookId']);

// get appointment details by book id
Route::middleware('auth:sanctum')->post('/appointment-details/book', [InvoiceController::class, 'getInvoiceDetailsByBookId']);

Route::get('/test-log', function () {
    Log::error('Test Telegram error from Laravel');

    return 'sent';
});

// order lead
Route::post('order_lead', [OrderController::class, 'store']);

Route::get('export/excel', [DashboardController::class, 'getAllPreMessagesPowerBi']);
Route::get('export/excel/status/{token}', [DashboardController::class, 'exportStatus'])
    ->name('power-bi-messages.export-status');

Route::middleware('powerbi.auth')
    ->get('power-bi-messages/current-month-link', [DashboardController::class, 'getCurrentMonthPreMessagesLink']);

Route::middleware('powerbi.auth')
    ->get('power-bi-messages/download', [DashboardController::class, 'downloadPreMessagesExport']);

Route::middleware('powerbi.auth')
    ->get('power-bi-messages/all', [DashboardController::class, 'getAllPreMessagesBi']);

// // ===== EXISTING — untouched, exactly as before =====
// Route::middleware('auth:sanctum')->prefix('settings')->group(function () {
//     Route::get('/appointment-cooldown', [SettingController::class, 'index']);
//     Route::put('/appointment-cooldown', [SettingController::class, 'updateCooldown']);
// });

// ===== NEW — added, not modifying anything above =====

// Public reads — no auth:sanctum
Route::prefix('settings')->group(function () {
    Route::get('/', [SettingController::class, 'indexAll']);
    Route::get('/{key}', [SettingController::class, 'show']);
});

// Protected writes — auth:sanctum required
Route::middleware('auth:sanctum')->prefix('settings')->group(function () {
    Route::post('/', [SettingController::class, 'store']);
    Route::put('/{key}', [SettingController::class, 'update']);
    Route::delete('/{key}', [SettingController::class, 'destroy']);
});

// System config health check — reports which required env vars are set,
// never their values. Restricted to super_admin inside the controller.
Route::middleware('auth:sanctum')->get('/admin/system/config-health', [SystemHealthController::class, 'configHealth']);

// Create-or-update a ChangeRequest for an appointment (matched by book_id + sales_order_id)
Route::post('/change-requests/upsert', [ChangeRequestController::class, 'upsert']);

Route::get('/specific/book_id/serial/{bookId}', [NewDirectIntegrationController::class, 'deleteAppointmentTransactionSerials']);

// Search for a specific serial across appointment transaction serials
Route::middleware('auth:sanctum')->post('/integration/appointment-transaction-serials/search', [NewDirectIntegrationController::class, 'searchAppointmentTransactionSerial']);

// Delete a serial if its owning appointment (checked live via DY365) is not found or not Completed
Route::middleware('auth:sanctum')->post('/integration/appointment-transaction-serials/cleanup', [NewDirectIntegrationController::class, 'deleteAppointmentTransactionSerial']);

// Technicians whose primary main warehouse matches the given MainWarehouseId
Route::middleware('auth:sanctum')->get('/integration/technicians/by-primary-warehouse', [NewDirectIntegrationController::class, 'techniciansByPrimaryWarehouse']);

// Delete a DirectAppointment and its payments by book_id
Route::middleware('auth:sanctum')->post('/integration/appointment/delete', [NewDirectIntegrationController::class, 'deleteDirectAppointment']);
