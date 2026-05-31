<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Brand-styled HTML email for the notifications hub's email channel.
 * Marketing sends carry a one-click unsubscribe footer (signed URL).
 */
class HubNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public ?string $unsubscribeUrl = null,
        public ?string $clinicName = null,
    ) {}

    public function build(): self
    {
        $mail = $this->subject($this->subjectLine)
            ->view('emails.hub-notification', [
                'bodyText' => $this->bodyText,
                'unsubscribeUrl' => $this->unsubscribeUrl,
                'clinicName' => $this->clinicName ?: config('app.name', 'Doctorato'),
            ]);

        // RFC 8058 one-click unsubscribe header for marketing mail.
        if ($this->unsubscribeUrl) {
            $mail->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$this->unsubscribeUrl.'>');
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
        }

        return $mail;
    }
}
