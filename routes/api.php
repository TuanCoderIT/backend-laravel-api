<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    ProfileController,
    ExamController,
    CategoryController,
    ResultController,
    UserController,
    QuestionController,
    RatingController,
    PaymentController,
    TransactionController,
    WalletController,
    PurchaseController,
    DocumentController
};
/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/exams', [ExamController::class, 'index']);
Route::get('/exams/{id}', [ExamController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
// Auth public
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password', 'resetPassword');
});
// Payment return (public)
Route::get('/payment/vnpay-return', [PaymentController::class, 'vnpayReturn'])
    ->name('payment.vnpay.return');

/*
|--------------------------------------------------------------------------
| Protected Routes (User)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Authenticated user
    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/user', 'user');
    });
    // Profile
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'show');
        Route::put('/profile', 'update');
    });
    // Results
    Route::controller(ResultController::class)->group(function () {
        Route::get('/results', 'index');
        Route::post('/results', 'store');
    });
    // Ratings
    Route::controller(RatingController::class)->group(function () {
        Route::get('/ratings', 'index');
        Route::post('/ratings', 'store');
        Route::get('/ratings/my', 'myRatings');
        Route::put('/ratings/{rating}', 'update');
        Route::delete('/ratings/{rating}', 'destroy');
        Route::get('/ratings/stats', 'stats');
    });
    // Documents
    Route::controller(DocumentController::class)->group(function () {
        Route::get('/documents', 'index');
        Route::get('/documents/{id}', 'show');
        Route::post('/documents', 'store');
        Route::post('/documents/{id}/versions', 'addVersion');
        Route::get('/documents/{id}/download', 'download');
    });
    // Wallet & Transactions
    Route::get('/me/wallet', [WalletController::class, 'show']);
    Route::get('/me/transactions', [TransactionController::class, 'index']);
    // Purchases
    Route::get('/me/purchases', [PurchaseController::class, 'listMyPurchases']);
    Route::post('/purchase', [PurchaseController::class, 'purchase']);
    Route::get('/purchase/check', [PurchaseController::class, 'check']);
    // Payments
    Route::controller(PaymentController::class)->group(function () {
        Route::post('/top-up', 'topUp');
        Route::get('/payment/callback', 'handleCallback');
    });
});
/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {
        Route::apiResource('exams', ExamController::class);
        Route::apiResource('questions', QuestionController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('users', UserController::class);
    });
