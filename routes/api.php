<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\CategoryController;

Route::get('exams', [ExamController::class, 'index']);
Route::get('exams/{id}', [ExamController::class, 'show']);

Route::get('categories', [CategoryController::class, 'index']);
