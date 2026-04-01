<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $mailSubject,
        protected string $mailBody,
        protected string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        $clinicName = Setting::get('clinic_name_en', 'Aura Derma Clinic');

        return new Envelope(
            subject: $this->mailSubject,
            replyTo: [Setting::get('clinic_email', config('mail.from.address'))],
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                $clinicName,
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-template',
            with: [
                'body' => $this->mailBody,
                'recipientName' => $this->recipientName,
                'clinicName' => Setting::get('clinic_name_en', 'Aura Derma Clinic'),
                'clinicPhone' => Setting::get('clinic_phone', ''),
            ],
        );
    }
}
