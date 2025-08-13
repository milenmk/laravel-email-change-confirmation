<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Facades;

use Illuminate\Support\Facades\Facade;
use MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService;

/**
 * @method static \MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange requestEmailChange(\Illuminate\Database\Eloquent\Model $user, string $newEmail)
 * @method static bool confirmEmailChange(\MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange $emailChange)
 * @method static bool denyEmailChange(\MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange $emailChange)
 * @method static \Illuminate\Database\Eloquent\Collection getPendingEmailChanges(\Illuminate\Database\Eloquent\Model $user)
 * @method static int cancelPendingEmailChanges(\Illuminate\Database\Eloquent\Model $user)
 * @method static bool validateEmailChange(\Illuminate\Database\Eloquent\Model $user, string $newEmail)
 *
 * @see \MilenMk\LaravelEmailChangeConfirmation\Services\EmailChangeService
 */
class EmailChangeConfirmation extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return EmailChangeService::class;
    }
}
