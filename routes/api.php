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
    DocumentController,
    CourseController,
    CourseChapterController,
    CourseLessonController,
    LearningController,
    PostController,
    CommentController,
    ReactionController,
    GroupController,
    GroupMemberController,
    GroupJoinRequestController,
    ChatController,
    RealtimeController,
    AIChatController,
    UserAIQuizController,
    NotificationController,
};
use App\Http\Controllers\Admin\{
    DashboardController,
    AdminPostController,
    AdminGroupController,
    AdminGroupOwnerController,
    AIQuizController,
};

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        // Route::post('/exams/ai-generate', [AIQuizController::class, 'generate']);
        Route::apiResource('exams', ExamController::class);
        Route::apiResource('questions', QuestionController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('courses', CourseController::class);
        Route::apiResource('documents', DocumentController::class);
        Route::prefix('courses/{course}')->group(function () {
            Route::get('chapters', [CourseChapterController::class, 'index']);
            Route::post('chapters', [CourseChapterController::class, 'store']);
            Route::get('chapters/{chapter}', [CourseChapterController::class, 'show']);
            Route::put('chapters/{chapter}', [CourseChapterController::class, 'update']);
            Route::delete('chapters/{chapter}', [CourseChapterController::class, 'destroy']);
        });
        Route::prefix('chapters/{chapter}')->group(function () {
            Route::get('lessons', [CourseLessonController::class, 'index']);
            Route::post('lessons', [CourseLessonController::class, 'store']);
            Route::get('lessons/{lesson}', [CourseLessonController::class, 'show']);
            Route::put('lessons/{lesson}', [CourseLessonController::class, 'update']);
            Route::delete('lessons/{lesson}', [CourseLessonController::class, 'destroy']);
        });
        // ===== GROUP =====
        Route::get('/groups', [AdminGroupController::class, 'index']);
        Route::get('/groups/{id}', [AdminGroupController::class, 'show']);
        Route::put('/groups/{id}', [AdminGroupController::class, 'update']);
        Route::delete('/groups/{id}', [AdminGroupController::class, 'destroy']);
        Route::post('/groups/{id}/lock', [AdminGroupController::class, 'lock']);

        // Owner
        Route::post('/groups/{id}/owner', [AdminGroupOwnerController::class, 'change']);

        // ===== MEMBERS =====
        Route::get('/groups/{groupId}/members', [AdminGroupController::class, 'index']);
        Route::delete('/groups/{groupId}/members/{userId}', [AdminGroupController::class, 'remove']);
        Route::post('/groups/{groupId}/members/{userId}/role', [AdminGroupController::class, 'changeRole']);

        // ===== POSTS =====
        Route::get('/posts', [AdminPostController::class, 'index']);
        Route::get('/posts/{id}', [AdminPostController::class, 'show']);
        Route::delete('/posts/{id}', [AdminPostController::class, 'destroy']);
        Route::post('/posts/{id}/hide', [AdminPostController::class, 'hide']);
    });

// Route::prefix('admin/community/reports')->middleware(['auth:sanctum', 'is_admin'])->group(function () {

//     // Xem danh sách report
//     Route::get('/', [AdminReportController::class, 'index']);

//     // Xử lý report
//     Route::post('/{id}/resolve', [AdminReportController::class, 'resolve']);
// });

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/exams/ai-generate', [AIQuizController::class, 'generate']);
Route::post('/exams/ai-from-file', [AIQuizController::class, 'generateFromFile']);

// User AI Quiz Routes (with full quiz info)
Route::post('/user/exams/ai-generate-from-file', [UserAIQuizController::class, 'generateFromFile']);
Route::post('/user/exams/ai-generate-from-prompt', [UserAIQuizController::class, 'generateFromPrompt']);

Route::get('/exams', [ExamController::class, 'index']);
Route::get('/exams/{id}', [ExamController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/documents', [DocumentController::class, 'index']);
Route::get('/documents/{id}', [DocumentController::class, 'show']);
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course:slug}', [CourseController::class, 'showBySlug']);
Route::get('/courses/{course:slug}/chapters', [CourseChapterController::class, 'index']);
Route::get('/chapters/{chapter}/lessons', [CourseLessonController::class, 'index']);
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

Route::get('/debug-token', function () {
    return dd(\App\Models\Course::with('tokenPricing')->first());
});

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
        Route::get('/documents/{id}/download', 'download');
    });
    // Wallet & Transactions
    Route::get('/me/wallet', [WalletController::class, 'show']);
    Route::get('/me/transactions', [TransactionController::class, 'index']);
    // Purchases
    Route::controller(PurchaseController::class)->group(function () {
        Route::get('/me/purchases', 'listMyPurchases');
        Route::post('/purchase', 'purchase');
        Route::get('/purchase/check', 'check');
    });
    // Payments
    Route::controller(PaymentController::class)->group(function () {
        Route::post('/top-up', 'topUp');
        Route::get('/payment/callback', 'handleCallback');
    });

    // Nhóm route courses/lessons
    Route::prefix('courses')->group(function () {
        // Xem bài học kèm trạng thái unlock + progress
        Route::get('{course:slug}/lessons/{lesson}', [LearningController::class, 'showLesson'])
            ->scopeBindings();
        // Học 1 bài (mark complete, mở khóa next)
        Route::post('{course:slug}/lessons/{lesson}/learn', [LearningController::class, 'learn'])
            ->scopeBindings();
        // Tiến độ khóa học (% tổng)
        Route::get('{course:slug}/progress', [LearningController::class, 'courseProgress'])
            ->scopeBindings();
    });

    // Các route chỉ cần lesson
    Route::prefix('lessons')->group(function () {
        // Cập nhật tiến độ xem bài (video/text)
        Route::post('{lesson}/progress', [LearningController::class, 'updateProgress'])
            ->scopeBindings();
        // Resume lesson
        Route::post('{lesson}/resume', [LearningController::class, 'resumeLesson'])
            ->scopeBindings();
        // Unlock lesson bằng token / logic mua
        Route::post('{lesson}/unlock', [LearningController::class, 'unlockLessonWithToken'])
            ->scopeBindings();
        // Lấy bài tiếp theo
        Route::get('{lesson}/next', [LearningController::class, 'nextLesson'])
            ->scopeBindings();
    });
    /*
    |--------------------------------------------------------------------------
    | POSTS
    |--------------------------------------------------------------------------
    */
    Route::prefix('posts')->group(function () {
        // Global feed
        Route::get('/', [PostController::class, 'index']);
        // Group feed
        Route::get('/group/{group}', [PostController::class, 'indexGroup']);
        // CRUD
        Route::post('/', [PostController::class, 'store']);
        Route::get('/{post}', [PostController::class, 'show']);
        Route::put('/{post}', [PostController::class, 'update']);
        Route::delete('/{post}', [PostController::class, 'destroy']);
        Route::post('/{post}/share', [PostController::class, 'share']);
        // Comments thuộc post
        Route::get('/{post}/comments', [CommentController::class, 'index']);
        Route::post('/{post}/comments', [CommentController::class, 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | COMMENTS (xoá)
    |--------------------------------------------------------------------------
    */
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | REACTIONS
    |--------------------------------------------------------------------------
    */
    Route::prefix('reactions')->group(function () {
        Route::post('/', [ReactionController::class, 'react']);     // add / update
        Route::delete('/', [ReactionController::class, 'remove']);  // remove
        Route::get('/', [ReactionController::class, 'getReactions']); // get reactions
    });

    /*
    |--------------------------------------------------------------------------
    | GROUPS
    |--------------------------------------------------------------------------
    */
    Route::prefix('groups')->group(function () {

        Route::get('/', [GroupController::class, 'index']);
        Route::post('/', [GroupController::class, 'store']);
        Route::get('/my-groups', [GroupController::class, 'myGroups']);
        Route::get('/{slug}', [GroupController::class, 'show']);
        Route::put('/{group}', [GroupController::class, 'update']);
        Route::delete('/{group}', [GroupController::class, 'destroy']);

        // MEMBER ACTIONS
        Route::get('/{groupId}/check-membership', [GroupController::class, 'checkMembership']);
        Route::get('/{groupId}/members', [GroupController::class, 'members']);
        Route::post('/{groupId}/join', [GroupMemberController::class, 'join']);
        Route::post('/{groupId}/leave', [GroupMemberController::class, 'leave']);
        Route::post('/{groupId}/kick/{userId}', [GroupMemberController::class, 'kick']);
        Route::post('/{groupId}/promote/{userId}', [GroupMemberController::class, 'promote']);
        Route::post('/{groupId}/demote/{userId}', [GroupMemberController::class, 'demote']);

        // JOIN REQUESTS (private groups)
        Route::get('/{groupId}/join-requests', [GroupJoinRequestController::class, 'list']);
        Route::post('/join-request/{requestId}/approve', [GroupJoinRequestController::class, 'approve']);
        Route::post('/join-request/{requestId}/reject', [GroupJoinRequestController::class, 'reject']);
    });

    /*
    |--------------------------------------------------------------------------
    | GROUP MEMBERS
    |--------------------------------------------------------------------------
    */
    Route::prefix('group-members')->group(function () {
        // Tham gia group
        Route::post('/join/{groupId}', [GroupMemberController::class, 'join']);
        // Rời group
        Route::post('/leave/{groupId}', [GroupMemberController::class, 'leave']);
    });

    Route::prefix('chat')->group(function () {
        // AI Assistant
        Route::post('/ai-assistant', [AIChatController::class, 'chat']);
        
        // Threads
        Route::get('/threads', [ChatController::class, 'myThreads']);
        Route::post('/threads/direct', [ChatController::class, 'directThread']);
        Route::get('/threads/group/{groupId}', [ChatController::class, 'groupThread']);
        // Messages
        Route::get('/threads/{id}/messages', [ChatController::class, 'messages']);
        Route::post('/threads/{id}/messages', [ChatController::class, 'send']);
        // Read / Typing
        Route::post('/threads/{id}/read', [ChatController::class, 'markRead']);
        Route::post('/threads/{id}/typing', [ChatController::class, 'typing']);
        // Reactions
        Route::post('/messages/{id}/react', [ChatController::class, 'reactMessage']);
        Route::delete('/messages/{id}/react', [ChatController::class, 'removeReaction']);
    });

    /*
    |--------------------------------------------------------------------------
    | REALTIME
    |--------------------------------------------------------------------------
    */
    Route::prefix('realtime')->group(function () {
        Route::get('/connection-info', [RealtimeController::class, 'getConnectionInfo']);
        Route::post('/test-connection', [RealtimeController::class, 'testConnection']);
    });

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->group(function () {
        // Danh sách và thống kê
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/stats', [NotificationController::class, 'stats']);
        
        // Actions
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
        
        // Individual notification
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        
        // Create (for testing/admin)
        Route::post('/', [NotificationController::class, 'store']);
    });

});
