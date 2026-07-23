<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $reportName,
        public readonly string $fileBytes,
        public readonly string $fileName,
        public readonly string $mimeType,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Scheduled Report: {$this->reportName}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.scheduled-report');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->fileBytes, $this->fileName)->withMime($this->mimeType),
        ];
    }
}
