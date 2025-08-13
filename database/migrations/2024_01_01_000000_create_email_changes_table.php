<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('email-change-confirmation.table_name', 'email_changes');
        $connection = config('email-change-confirmation.connection');

        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary();

            // User relationship - flexible to work with different user table structures
            $userModel = config('email-change-confirmation.user_model', config('auth.providers.users.model'));
            $userTable = (new $userModel)->getTable();
            $userKeyType = (new $userModel)->getKeyType();

            if ($userKeyType === 'int') {
                $table->unsignedBigInteger('user_id');
            } else {
                $table->uuid('user_id');
            }

            $table
                ->foreign('user_id')
                ->references('id')
                ->on($userTable)
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

    public function down(): void
    {
        $tableName = config('email-change-confirmation.table_name', 'email_changes');
        $connection = config('email-change-confirmation.connection');

        Schema::connection($connection)->dropIfExists($tableName);
    }
};
