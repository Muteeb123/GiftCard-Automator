<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GiftCardBatchReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected string $filePath;
    protected int $batchId;

    /**
     * Create a new message instance.
     */
    public function __construct(string $filePath, int $batchId)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        try {
            $subject = "Gift Card Batch Report #{$this->batchId}";
            $bodyText = "Hello,\n\nYour gift card batch #{$this->batchId} has been processed successfully.\n\nPlease find the attached Excel report below.";

            $email = $this->subject($subject)
                ->view('emails.giftcard_report') // optional view
                ->with([
                    'batchId' => $this->batchId,
                ]);

            // ✅ If file exists, attach it
            if (file_exists($this->filePath)) {
                $email->attach($this->filePath, [
                    'as' => "gift_cards_batch_{$this->batchId}.xlsx",
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            } else {
                Log::warning("⚠️ Attachment file not found for GiftCardBatchReportMail: {$this->filePath}");
            }

            return $email;
        } catch (\Throwable $e) {
            Log::error("❌ Failed to build GiftCardBatchReportMail: {$e->getMessage()}");
            // Return a minimal fallback email to avoid job crash
            return $this->subject("Gift Card Batch #{$this->batchId} Report")
                ->text('emails.fallback')
                ->with(['message' => 'There was an issue generating your report, please contact support.']);
        }
    }
}
