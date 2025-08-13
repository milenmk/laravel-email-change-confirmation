<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MilenMk\LaravelEmailChangeConfirmation\Observers\UserObserver;
use MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService;

class EmailChangeConfirmationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/email-change-confirmation.php', 'email-change-confirmation');

        $this->app->singleton(EmailChangeService::class, function ($app) {
            return new EmailChangeService;
        });

        // Register the facade
        $this->app->alias(EmailChangeService::class, 'email-change-confirmation');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes(
            [
                __DIR__ . '/../config/email-change-confirmation.php' => config_path('email-change-confirmation.php'),
            ],
            'email-change-confirmation-config',
        );

        $this->publishes(
            [
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ],
            'email-change-confirmation-migrations',
        );

        $this->registerRoutes();
        $this->registerObserver();
        $this->validateSecurityConfiguration();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (! $this->app->routesAreCached()) {
            Route::group(
                [
                    'middleware' => config('email-change-confirmation.middleware', ['web']),
                    'prefix' => config('email-change-confirmation.route_prefix', 'email-change'),
                    'as' => 'email-change-confirmation.',
                ],
                function () {
                    $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
                },
            );
        }
    }

    /**
     * Register the user observer if auto-detection is enabled.
     */
    protected function registerObserver(): void
    {
        if (config('email-change-confirmation.auto_detect_email_changes', true)) {
            $userModel = config('email-change-confirmation.user_model', config('auth.providers.users.model'));

            if ($userModel && class_exists($userModel)) {
                $userModel::observe(UserObserver::class);
            }
        }
    }

    /**
     * Validate security configuration and log warnings.
     */
    protected function validateSecurityConfiguration(): void
    {
        $expireMinutes = config('email-change-confirmation.confirmation_email_expire_minutes', 60);
        if ($expireMinutes > 1440) {
            // 24 hours
            Log::warning(
                'Email change confirmation expiration is set to more than 24 hours, which may pose security risks',
                [
                    'current_setting' => $expireMinutes,
                    'recommended_max' => 1440,
                ],
            );
        }

        $hashSecret = config('email-change-confirmation.hash_secret');
        if (! $hashSecret) {
            Log::warning(
                'EMAIL_CHANGE_HASH_SECRET not configured - email hashes will use weaker security. Set EMAIL_CHANGE_HASH_SECRET in your .env file.',
            );
        } elseif (strlen($hashSecret) < 16) {
            Log::warning('EMAIL_CHANGE_HASH_SECRET is too short for optimal security', [
                'current_length' => strlen($hashSecret),
                'minimum_recommended' => 16,
                'optimal_length' => 32,
            ]);
        } elseif (strlen($hashSecret) < 32) {
            Log::info('EMAIL_CHANGE_HASH_SECRET meets minimum requirements but could be stronger', [
                'current_length' => strlen($hashSecret),
                'recommended_length' => 32,
            ]);
        }

        // Check for weak/predictable secrets
        if ($hashSecret && $this->isWeakHashSecret($hashSecret)) {
            Log::warning(
                'EMAIL_CHANGE_HASH_SECRET appears to be weak or predictable. Use a cryptographically secure random string.',
            );
        }

        $maxRequests = config('email-change-confirmation.max_requests_per_hour', 5);
        if ($maxRequests > 20) {
            Log::warning('High rate limit for email change requests may allow abuse', [
                'current_setting' => $maxRequests,
                'recommended_max' => 20,
            ]);
        }
    }

    /**
     * Check if the hash secret appears to be weak or predictable.
     */
    protected function isWeakHashSecret(string $secret): bool
    {
        $weakPatterns = [
            'secret',
            'password',
            'key',
            'hash',
            'test',
            'dev',
            'demo',
            'example',
            '123',
            'abc',
            'qwerty',
            'admin',
        ];

        $lowerSecret = strtolower($secret);

        foreach ($weakPatterns as $pattern) {
            if (strpos($lowerSecret, $pattern) !== false) {
                return true;
            }
        }

        // Check for repeated characters (like "aaaaaaaaaa")
        if (strlen($secret) > 8 && count(array_unique(str_split($secret))) < 4) {
            return true;
        }

        return false;
    }
}
