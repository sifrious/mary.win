<?php

namespace App\Mail;

use App\Models\MailingListSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailingListConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MailingListSubscription $subscription,
        public string $confirmationToken,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your email subscription',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.mailing-list-confirmation',
            text: 'mail.mailing-list-confirmation-text',
            with: [
                'confirmUrl' => route('mailing-list.confirm', ['token' => $this->confirmationToken]),
                'consentText' => $this->subscription->consent_text,
            ],
        );
    }
}
