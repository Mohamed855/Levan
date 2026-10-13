<?php

namespace App\Mail\Tenants\Letter;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailSubject;

    public $emailBody;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(array $preview)
    {
        $this->emailSubject = $preview['subject'];
        $this->emailBody = $preview['preview'];
    }

    /*
    |--------------------------------------------------------------------------
    | Envelope - معلومات البريد
    |--------------------------------------------------------------------------
    */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Content - محتوى البريد
    |--------------------------------------------------------------------------
    */
    public function content(): Content
    {
        return new Content(
            view: 'emails.company-letter',
            with: [
                'subject' => $this->emailSubject,
                'body' => $this->emailBody,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Attachments - المرفقات (إذا لزم الأمر)
    |--------------------------------------------------------------------------
    */
    public function attachments(): array
    {
        return [];
    }
}
