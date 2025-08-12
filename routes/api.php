<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\PurchaseController;

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    // Results & Profile management
    Route::post('/results', [ResultController::class, 'store']);
    Route::get('/results', [ResultController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    // Ratings management
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::get('/ratings', [RatingController::class, 'index']);
    Route::get('/ratings/my', [RatingController::class, 'myRatings']);
    Route::put('/ratings/{rating}', [RatingController::class, 'update']);
    Route::delete('/ratings/{rating}', [RatingController::class, 'destroy']);
    Route::get('/ratings/stats', [RatingController::class, 'stats']);
    // Payment routes
    Route::post('/top-up', [PaymentController::class, 'topUp']);
    Route::get('/payment/callback', [PaymentController::class, 'handleCallback']);
    // Transaction routes
    Route::get('/me/transactions', [TransactionController::class, 'index']);
    Route::get('/me/wallet', [WalletController::class, 'show']);
    Route::get('/me/purchases', [PurchaseController::class, 'listMyPurchases']);
    // Purchase routes
    Route::post('/purchase', [PurchaseController::class, 'purchase']);
    Route::get('/purchase/check', [PurchaseController::class, 'check']); // tuỳ chọn
});
// Payment return route
Route::get('/payment/vnpay-return', [PaymentController::class, 'vnpayReturn'])->name('payment.vnpay.return');

// Public routes
Route::get('/exams', [ExamController::class, 'index']);
Route::get('/exams/{id}', [ExamController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);


// Admin routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Exam management routes
    Route::post('/exams', [ExamController::class, 'store']);
    Route::get('/exams', [ExamController::class, 'index']);
    Route::get('/exams/{id}', [ExamController::class, 'show']);
    Route::put('/exams/{id}', [ExamController::class, 'update']);
    Route::delete('/exams/{id}', [ExamController::class, 'destroy']);

    // Question management routes
    Route::post('/questions', [QuestionController::class, 'store']);
    Route::get('/questions', [QuestionController::class, 'index']);
    Route::get('/questions/{id}', [QuestionController::class, 'show']);
    Route::put('/questions/{id}', [QuestionController::class, 'update']);
    Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);

    // Category management routes
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // User management routes
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});
