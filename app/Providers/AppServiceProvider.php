<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use App\Models\Course;
use App\Policies\CoursePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return 'http://localhost:3000/auth/reset-password?token=' . $token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                // Khai báo Bearer Token authentication
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });

        Gate::define('viewApiDocs', function (?object $user = null, ?Request $request = null) {
            if (app()->environment('local')) {
                return true;
            }

            return (bool) env('SCRAMBLE_ALLOW_DOCS', false);
        });
    }
    
    protected $policies = [
        Course::class => CoursePolicy::class,
    ];
    
}
