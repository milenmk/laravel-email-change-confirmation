<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MilenMk\LaravelEmailChangeConfirmation\EmailChangeConfirmationServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('email_changes');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [EmailChangeConfirmationServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('auth.providers.users.model', TestUser::class);
        $app['config']->set('email-change-confirmation.user_model', TestUser::class);
        $app['config']->set('email-change-confirmation.table_name', 'email_changes');
        $app['config']->set('email-change-confirmation.connection', null);

        $app['config']->set('mail.default', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
    }

    protected function setUpDatabase(): void
    {
        // Create users table for testing
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Create email_changes table manually to avoid config issues
        if (! Schema::hasTable('email_changes')) {
            Schema::create('email_changes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id');
                $table
                    ->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
                $table->string('current_email');
                $table->string('new_email');
                $table->timestamp('change_confirmed_at')->nullable();
                $table->timestamp('change_denied_at')->nullable();
                $table->timestamps();

                // Indexes for performance
                $table->index('user_id');
                $table->index(['user_id', 'created_at']);
                $table->index('change_confirmed_at');
                $table->index('change_denied_at');
            });
        }
    }

    protected function createUser(array $attributes = []): TestUser
    {
        return TestUser::create(
            array_merge(
                [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                ],
                $attributes,
            ),
        );
    }
}
