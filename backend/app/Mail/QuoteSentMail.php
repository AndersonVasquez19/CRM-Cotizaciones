<?php

namespace App\Mail;

use App\Models\Quote;
use App\Services\QuotePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $portalUrl;

    public function __construct(
        public readonly Quote $quote,
        ?string $frontendUrl = null,
    ) {
        $base = rtrim($frontendUrl ?: config('app.frontend_url', 'http://localhost:3000'), '/');
        $this->portalUrl = "{$base}/p/quotes/{$quote->public_token}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Cotización Técnica {$this->quote->quote_number} (v{$this->quote->version}) — TechQuote Integraciones",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote_sent',
        );
    }

    public function attachments(): array
    {
        $pdfBytes = app(QuotePdfService::class)->output($this->quote);
        $filename = "{$this->quote->quote_number}-v{$this->quote->version}.pdf";

        return [
            Attachment::fromData(fn () => $pdfBytes, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
