<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MarginApprovalRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $quoteUrl;

    public function __construct(
        public readonly Quote $quote,
        public readonly float $marginPercent,
        public readonly float $minMarginPercent,
    ) {
        $base = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
        $this->quoteUrl = "{$base}/quotes/{$quote->id}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Aprobación Requerida] {$this->quote->quote_number} v{$this->quote->version} — Margen {$this->marginPercent}%",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.margin_approval',
        );
    }
}
