<?php

namespace App\Notifications;

use MilenMk\LaravelEmailChangeConfirmation\Notifications\EmailChangeConfirmation as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Example of customizing the email change confirmation notification.
 * Update your config to use this notification:
 * 
 * 'email_change_notification' => App\Notifications\CustomEmailChangeNotification::class,
 */
class CustomEmailChangeNotification extends BaseNotification
{
    /**
     * Customize the mail message
     */
    protected function buildMailMessage(string $confirmUrl, string $denyUrl): MailMessage
    {
        $appName = config('app.name');
        $expireMinutes = config('email-change-confirmation.confirmation_email_expire_minutes', 60);

        return (new MailMessage)
            ->subject("🔐 Confirm Your Email Change - {$appName}")
            ->greeting("Hello {$this->username}! 👋")
            ->line("We received a request to change your email address on **{$appName}**.")
            ->line("**Current email:** {$this->oldEmail}")
            ->line("**New email:** {$this->newEmail}")
            ->line("If you made this request, please click the button below to confirm:")
            ->action('✅ Confirm Email Change', $confirmUrl)
            ->line("---")
            ->line("⚠️ **Security Alert**: If you did NOT request this change, your account may be compromised.")
            ->line("Please click the button below immediately to deny this request and secure your account:")
            ->action('🚫 DENY REQUEST', $denyUrl)
            ->line("---")
            ->line("📋 **Additional Information:**")
            ->line("• This request was made on: " . now()->format('F j, Y \a\t g:i A T'))
            ->line("• This link will expire in {$expireMinutes} minutes")
            ->line("• If you're having trouble with the buttons, you can copy and paste these URLs:")
            ->line("**Confirm:** {$confirmUrl}")
            ->line("**Deny:** {$denyUrl}")
            ->line("---")
            ->line("If you have any questions, please contact our support team.")
            ->salutation("Best regards,\nThe {$appName} Team");
    }

    /**
     * Customize the email channels (add SMS, Slack, etc.)
     */
    public function via(mixed $notifiable): array|string
    {
        $channels = ['mail'];

        // Add SMS notification for high-value users
        if (method_exists($notifiable, 'isVip') && $notifiable->isVip()) {
            $channels[] = 'sms';
        }

        // Add Slack notification for admin users
        if (method_exists($notifiable, 'isAdmin') && $notifiable->isAdmin()) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Get the SMS representation of the notification
     */
    public function toSms(mixed $notifiable): string
    {
        return "Security Alert: Email change request for your {$this->getAppName()} account. " .
               "New email: {$this->newEmail}. " .
               "If this wasn't you, contact support immediately.";
    }

    /**
     * Get the Slack representation of the notification
     */
    public function toSlack(mixed $notifiable): \Illuminate\Notifications\Messages\SlackMessage
    {
        return (new \Illuminate\Notifications\Messages\SlackMessage)
            ->warning()
            ->content('Admin Email Change Request')
            ->attachment(function ($attachment) use ($notifiable) {
                $attachment->title('Email Change Details')
                    ->fields([
                        'User' => $this->username,
                        'User ID' => $notifiable->getKey(),
                        'Current Email' => $this->oldEmail,
                        'New Email' => $this->newEmail,
                        'Time' => now()->format('Y-m-d H:i:s T'),
                    ]);
            });
    }

    /**
     * Helper method to get app name
     */
    protected function getAppName(): string
    {
        return config('app.name', 'Laravel');
    }
}