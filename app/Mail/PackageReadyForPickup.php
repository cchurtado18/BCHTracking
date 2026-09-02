<?php

namespace App\Mail;

use App\Models\Preregistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackageReadyForPickup extends Mailable
{
    use Queueable, SerializesModels;

    public string $trackingUrl;

    public string $packageCode;

    public function __construct(public Preregistration $package)
    {
        $this->packageCode = (string) ($package->warehouse_code ?: $package->tracking_external ?: $package->id);
        $this->trackingUrl = route('tracking.index', ['code' => $this->packageCode]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paquete '.$this->packageCode.' listo para retiro — PrimeTrack Group',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.package-ready-pickup',
        );
    }
}
