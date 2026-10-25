<?php

use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Invoice\ShortLinkController;
use App\Http\Controllers\Api\Pdf\Invoice2Controller;
use App\Http\Controllers\Api\Pdf\InvoiceController;
use App\Http\Controllers\Api\User\UserStockController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPdf\Facades\Pdf;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
//export
Route::get('dashboard/sheet', [DashboardController::class, 'getAllPreMessagesPowerBiOld']);


Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::get('/payment-success', function () {
    $status = 'failed';
    $payment_type = 'tamara';
    // Get from query string
    return view('Payment.result', compact('status', 'payment_type'));
})->name('payment.success');


Route::get('user-stock/export-all', [UserStockController::class, 'exportAllUsersStock']);

Route::get('/i/{code}', [ShortLinkController::class, 'show'])->name('shortlink.show');


Route::get('/invoice/{id}', [Invoice2Controller::class, 'download'])
    ->name('invoice.pdf');
Route::get('/invoice/{id}/stream', [InvoiceController::class, 'stream'])
    ->name('invoice.stream');

Route::get('/invoice2/{id}/stream', [Invoice2Controller::class, 'stream'])
    ->name('invoice2.stream');


Route::get('/login', function () {
    return redirect('/api/auth/send-otp');
})->name('login');

// Shorter short-link format: https://{domain}/{code} instead of /i/{code}. The regex constraint — exactly
// 8 alphanumeric characters, matching ShortLinkCodeGenerator's output — is what makes a root-level wildcard
// route safe here: it can only ever match an actual short-link code, never a real route name like "login"
// (5 characters) or "payment-success" (contains a hyphen), so it can't shadow anything above it. Registered
// last anyway, as a second layer of safety, so any future route added above it is never at risk either way.
// /i/{code} (above) is kept working indefinitely — links already sent to customers before this change must
// keep resolving.
Route::get('/{code}', [ShortLinkController::class, 'show'])
    ->where('code', '[a-zA-Z0-9]{8}')
    ->name('shortlink.show.short');
