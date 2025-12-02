<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BookingController;

// Redirect root ke register
Route::get('/', function () {
    return redirect()->route('register');
});

// Auth Routes
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Login OTP Routes
Route::get('/login/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('login.verify');
Route::post('/login/verify-otp', [AuthController::class, 'verifyOtp'])->name('login.verifyOtp');
Route::post('/login/resend-otp', [AuthController::class, 'resendLoginOtp'])->name('login.resendOtp');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {
    // Home
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/room/{id}', [HomeController::class, 'show'])->name('room.show');
    
    // Booking
    Route::get('/booking/create/{roomId}', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/booking/store', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/booking/confirm/{id}', [BookingController::class, 'confirm'])->name('booking.confirm');
    Route::post('/booking/verify-otp/{id}', [BookingController::class, 'verifyOtp'])->name('booking.verifyOtp');
    
    // Payment Routes
    Route::get('/booking/payment/{id}', [BookingController::class, 'showPayment'])->name('booking.payment');
    Route::post('/booking/process-payment/{id}', [BookingController::class, 'processPayment'])->name('booking.processPayment');
    Route::get('/booking/verify-payment/{id}', [BookingController::class, 'showVerifyPayment'])->name('booking.verifyPayment');
    Route::post('/booking/verify-payment-otp/{id}', [BookingController::class, 'verifyPaymentOtp'])->name('booking.verifyPaymentOtp');
    Route::post('/booking/resend-payment-otp/{id}', [BookingController::class, 'resendPaymentOtp'])->name('booking.resendPaymentOtp');
    
    Route::get('/booking/success/{id}', [BookingController::class, 'success'])->name('booking.success');
    Route::get('/my-bookings', [BookingController::class, 'myBookings'])->name('booking.myBookings');

    // Metrics endpoint
    Route::get('/metrics', [App\Http\Controllers\MetricsController::class, 'metrics']);
});