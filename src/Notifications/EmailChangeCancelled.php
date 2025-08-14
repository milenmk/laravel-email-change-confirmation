<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeCancelled extends Notification
{
    use Queueable;

    protected string $cancelledEmail;
    protected int $cancelledCount;

    public function __construct(string $cancelledEmail, int $cancelledCount = 1)
    {
        $this->cancelledEmail = $cancelledEmail;
        $this->cancelledCount = $cancelledCount;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = 'Email Change Request Cancelled';
        $greeting = 'Hello!';

        if ($this->cancelledCount === 1) {
            $message = "Your email change request to **{$this->cancelledEmail}** has been cancelled.";
        } else {
            $message = "Your {$this->cancelledCount} pending email change requests have been cancelled.";
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($message)
            ->line('If you did not cancel this request, please contact support immediately.')
            ->line('You can request a new email change at any time from your profile settings.');
    }
}
