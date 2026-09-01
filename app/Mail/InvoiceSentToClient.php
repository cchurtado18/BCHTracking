<?php

namespace App\Mail;

use App\Models\AccountingInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class InvoiceSentToClient extends Mailable
{
    use Queueable, SerializesModels;

    public string $voucherUrl;

    public function __construct(public AccountingInvoice $invoice)
    {
        $this->voucherUrl = URL::temporarySignedRoute(
            'accounting.invoices.public-voucher',
            now()->addDays(30),
            ['invoice' => $invoice->id]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factura '.$this->invoice->folio.' — PrimeTrack Group',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice-sent',
        );
    }
}
