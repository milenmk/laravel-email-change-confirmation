<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;

class EmailChangeConfirmation extends Notification
{
    protected EmailChange $emailChange;
    protected string $username;
    protected string $oldEmail;
    protected string $newEmail;

    public function __construct(EmailChange $emailChange)
    {
        $this->emailChange = $emailChange;
        $this->username = $this->getUserDisplayName($emailChange->user);
        $this->oldEmail = $emailChange->current_email;
        $this->newEmail = $emailChange->new_email;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array|string
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $confirmUrl = $this->confirmUrl($notifiable);
        $denyUrl = $this->denyUrl($notifiable);

        return $this->buildMailMessage($confirmUrl, $denyUrl);
    }

    /**
     * Get the confirmation URL.
     */
    protected function confirmUrl(mixed $notifiable): string
    {
        $expireMinutes = config('email-change-confirmation.confirmation_email_expire_minutes', 60);

        return URL::temporarySignedRoute(
            'email-change-confirmation.confirm',
            Carbon::now()->addMinutes($expireMinutes),
            [
                'id' => $notifiable->getKey(),
                'hash' => $this->generateHash($this->oldEmail),
                'email_change' => $this->emailChange->getKey(),
            ],
        );
    }

    /**
     * Get the denial URL.
     */
    protected function denyUrl(mixed $notifiable): string
    {
        $expireMinutes = config('email-change-confirmation.confirmation_email_expire_minutes', 60);

        return URL::temporarySignedRoute(
            'email-change-confirmation.deny',
            Carbon::now()->addMinutes($expireMinutes),
            [
                'id' => $notifiable->getKey(),
                'hash' => $this->generateHash($this->oldEmail),
                'email_change' => $this->emailChange->getKey(),
            ],
        );
    }

    /**
     * Generate hash for the email.
     */
    protected function generateHash(string $email): string
    {
        $algorithm = config('email-change-confirmation.hash_algorithm', 'sha256');
        $secret = config('email-change-confirmation.hash_secret');

        if ($secret) {
            // Use HMAC for better security
            return hash_hmac($algorithm, $email, $secret);
        }

        // Fallback to simple hash for backward compatibility
        // Log warning about weak security
        Log::warning('EMAIL_CHANGE_HASH_SECRET not configured - using weak hashing');

        return hash($algorithm, $email);
    }

    /**
     * Build the mail message.
     */
    protected function buildMailMessage(string $confirmUrl, string $denyUrl): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->greeting(__('Hello :user', ['user' => $this->username]))
            ->subject(__('Email Change Request Confirmation'))
            ->line(__('A request to change your account email address to **:new_email** has been made.', [
                'new_email' => $this->newEmail,
            ]))
            ->line(__('If the request is genuine, please click the confirmation button below to confirm the change.'))
            ->action(__('Confirm Email Change'), $confirmUrl)
            ->success();

        // Add security warning and deny button
        $mailMessage
            ->line(__('If you did not request this change, it is possible that your account has been compromised. Please click the button below to deny this request and secure your account.'))
            ->line($this->buildDenyButton($denyUrl))
            ->line(new HtmlString(__('If you\'re having trouble clicking the buttons, copy and paste the URLs below into your web browser:')))
            ->line(__('Confirm: :url', ['url' => $confirmUrl]))
            ->line(__('Deny: :url', ['url' => $denyUrl]));

        // Set custom from address if configured
        $fromEmail = config('email-change-confirmation.from_email');
        $fromName = config('email-change-confirmation.from_name');

        if ($fromEmail) {
            $mailMessage->from($fromEmail, $fromName);
        }

        return $mailMessage;
    }

    /**
     * Build the deny button HTML.
     */
    protected function buildDenyButton(string $denyUrl): HtmlString
    {
        return new HtmlString(
            '<table class="action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative; margin: 30px auto; padding: 0; text-align: center; width: 100%;">
                <tr>
                    <td align="center" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative;">
                        <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative;">
                            <tr>
                                <td align="center" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative;">
                                        <tr>
                                            <td style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative;">
                                                <a href="' . $denyUrl . '" class="button button-red" target="_blank" rel="noopener" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif, \'Apple Color Emoji\', \'Segoe UI Emoji\', \'Segoe UI Symbol\'; position: relative; -webkit-text-size-adjust: none; border-radius: 4px; color: #fff; display: inline-block; overflow: hidden; text-decoration: none; background-color: #dc3545; border-bottom: 8px solid #dc3545; border-left: 18px solid #dc3545; border-right: 18px solid #dc3545; border-top: 8px solid #dc3545;">' . __('DENY REQUEST') . '</a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>'
        );
    }

    /**
     * Get user display name.
     */
    protected function getUserDisplayName($user): string
    {
        // Try different common name attributes
        if (isset($user->full_name)) {
            return $user->full_name;
        }

        if (isset($user->name) && isset($user->last_name)) {
            return $user->name . ' ' . $user->last_name;
        }

        if (isset($user->first_name) && isset($user->last_name)) {
            return $user->first_name . ' ' . $user->last_name;
        }

        if (isset($user->name)) {
            return $user->name;
        }

        if (isset($user->first_name)) {
            return $user->first_name;
        }

        // Fallback to email
        return $user->email;
    }
}
