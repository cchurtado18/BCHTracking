<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PackageAlertsDigest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Collection $alerts)
    {
    }

    public function envelope(): Envelope
    {
        $n = $this->alerts->count();

        return new Envelope(
            subject: $n === 1
                ? '1 paquete necesita revisión — PrimeTrack'
                : $n.' paquetes necesitan revisión — PrimeTrack',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.package-alerts',
        );
    }
}
