<?php

namespace App\Mail;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Rental $rental) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reservation Confirmation - Order #' . $this->rental->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-pdf',
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('emails.order-pdf', ['rental' => $this->rental]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'Order_Receipt_' . $this->rental->id . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}