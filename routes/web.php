<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitePageController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [SitePageController::class, 'about'])->name('site.about');
Route::get('/contact', [SitePageController::class, 'contact'])->name('site.contact');
Route::get('/privacy', [SitePageController::class, 'privacy'])->name('site.privacy');

Route::middleware('throttle:otp-send')->group(function () {
    Route::post('/requests/otp', [SubmissionController::class, 'sendOtp'])->name('requests.otp.send');
    Route::post('/track/otp', [TrackingController::class, 'sendOtp'])->name('track.otp.send');
});

Route::middleware('throttle:otp-verify')->group(function () {
    Route::post('/requests/otp/verify', [SubmissionController::class, 'verifyOtp'])->name('requests.otp.verify');
    Route::post('/track/otp/verify', [TrackingController::class, 'verifyOtp'])->name('track.otp.verify');
});

Route::get('/requests/new', [SubmissionController::class, 'create'])->name('requests.create');
Route::post('/requests', [SubmissionController::class, 'store'])->middleware('throttle:10,1')->name('requests.store');
Route::get('/requests/success', [SubmissionController::class, 'success'])->name('requests.success');

Route::get('/track', [TrackingController::class, 'show'])->name('track.show');
Route::get('/track/access/bootstrap', [TrackingController::class, 'bootstrapAccess'])->name('track.access.bootstrap');
Route::post('/track/attachments', [TrackingController::class, 'upload'])->middleware('throttle:10,1')->name('track.attachments.store');
Route::post('/track/logout', [TrackingController::class, 'logout'])->name('track.logout');
Route::get('/track/attachments/{attachment}', [AttachmentController::class, 'customer'])->name('track.attachments.show');

Route::middleware('auth')->group(function () {
    Route::get('/staff/attachments/{attachment}', [AttachmentController::class, 'staff'])->name('staff.attachments.show');
});
