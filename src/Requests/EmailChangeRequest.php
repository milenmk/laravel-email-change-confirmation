<?php

declare(strict_types=1);

namespace MilenMk\LaravelEmailChangeConfirmation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;

class EmailChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verify the user ID matches
        if (! hash_equals((string) $this->user()->getKey(), (string) $this->route('id'))) {
            return false;
        }

        // Find the email change record
        $emailChange = $this->getEmailChange();
        if (! $emailChange) {
            return false;
        }

        // Verify the hash matches the current email
        $expectedHash = $this->generateHash($emailChange->current_email);
        if (! hash_equals($expectedHash, (string) $this->route('hash'))) {
            return false;
        }

        // Verify the email change belongs to the user
        if (! hash_equals((string) $emailChange->user_id, (string) $this->user()->getKey())) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // No additional validation rules needed as authorization handles verification
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'authorize' => 'Invalid or expired email change request.',
        ];
    }

    /**
     * Get the email change record from the route.
     */
    protected function getEmailChange(): ?EmailChange
    {
        $emailChangeModel = config('email-change-confirmation.email_change_model');

        return $emailChangeModel::find($this->route('email_change'));
    }

    /**
     * Generate hash for the given email.
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
}
