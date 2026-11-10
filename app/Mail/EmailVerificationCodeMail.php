<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationUrl;

    public function __construct(
        public User $user,
        public string $code
    ) {
        $this->verificationUrl = route('verification.link', [
            'id' => $this->user->id,
            'token' => $this->code,
        ]);
    }

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from.address', 'albanisetiawan52@gmail.com');
        $fromName = (string) config('mail.from.name', 'Perpustakaan SMKN 5 Surakarta');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            replyTo: [
                new Address($fromAddress, $fromName),
            ],
            subject: 'Konfirmasi Akun Perpustakaan SMK Negeri 5 Surakarta',
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Auto-Response-Suppress' => 'OOF, AutoReply',
                'X-Entity-Ref-ID' => 'bupus-verify-'.$this->user->id.'-'.time(),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.verification-code',
            text: 'emails.verification-code-text',
        );
    }
}
