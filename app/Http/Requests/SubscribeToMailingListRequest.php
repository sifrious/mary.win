<?php

namespace App\Http\Requests;

use App\Models\MailingListSubscription;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Validator;

class SubscribeToMailingListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc,filter', 'max:254'],
            'consent' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'consent' => 'consent checkbox',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Enter the email address you want to subscribe.',
            'email.email' => 'Enter an email address in the form name@example.com.',
            'email.max' => 'That email address is too long.',
            'consent.accepted' => 'Tick the consent box so we know you want these emails.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Honeypot: a field no sighted or assistive-technology user is shown.
            // Anything in it came from a bot filling every input on the page.
            if (filled($this->input(config('mailing-list.honeypot_field')))) {
                $validator->errors()->add('email', 'That submission looked automated. Please try again.');

                return;
            }

            $this->validateFillTime($validator);
        });
    }

    /**
     * Reject submissions that arrive implausibly fast after the form was
     * rendered. The timestamp is encrypted, so it cannot be forged or replayed
     * with an arbitrary value, and the check needs no JavaScript.
     */
    protected function validateFillTime(Validator $validator): void
    {
        $minimum = (int) config('mailing-list.min_fill_seconds');

        if ($minimum <= 0) {
            return;
        }

        $renderedAt = $this->input('form_rendered_at');

        if (! is_string($renderedAt) || $renderedAt === '') {
            $validator->errors()->add('email', 'This form expired. Please submit it again.');

            return;
        }

        try {
            $timestamp = (int) Crypt::decryptString($renderedAt);
        } catch (DecryptException) {
            $validator->errors()->add('email', 'This form expired. Please submit it again.');

            return;
        }

        if (time() - $timestamp < $minimum) {
            $validator->errors()->add('email', 'That submission looked automated. Please try again.');
        }
    }

    public function normalizedEmail(): string
    {
        return MailingListSubscription::normalizeEmail((string) $this->input('email'));
    }
}
