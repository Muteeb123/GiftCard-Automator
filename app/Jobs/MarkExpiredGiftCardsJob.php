<?php

namespace App\Jobs;

use App\Models\GiftCardBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MarkExpiredGiftCardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $batchId;

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function handle()
    {
        $batch = GiftCardBatch::find($this->batchId);

        if (!$batch) {
            Log::warning("⚠️ Batch not found for expiration: {$this->batchId}");
            return;
        }

        // Update only generated gift cards that are not used and not expired
        $expiredCount = $batch->generatedGiftCards()
            ->whereNotIn('status', ['used', 'expired'])
            ->update(['status' => 'expired']);

        Log::info("🕒 Expired {$expiredCount} gift cards for batch #{$this->batchId}");
    }
}
