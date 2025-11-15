<?php

namespace App\Jobs;

use App\Models\GiftCardBatch;
use App\Models\GeneratedGiftCard;
use App\Models\User;
use App\Providers\ShopifyGiftCardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\GiftCardBatchReportMail;
use App\Exports\GiftCardsExport;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CreateGiftCards implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;

    public function __construct(GiftCardBatch $batch)
    {
        $this->batch = $batch;
    }

    public function handle()
    {
        Log::info("🎁 Starting GiftCardBatch #{$this->batch->id}");

        $this->batch->update(['status' => 'in_progress']);

        $user = User::find($this->batch->user_id);
        if (!$user) {
            Log::error("❌ User not found for GiftCardBatch #{$this->batch->id}");
            $this->batch->update(['status' => 'failed']);
            return;
        }

        $service = new ShopifyGiftCardService($user);
        $successCount = 0;
        $failureCount = 0;

        for ($i = 0; $i < $this->batch->gift_card_count; $i++) {
            $attempt = $i + 1;

            // ✅ Generate manual code (with prefix if exists)
            $manualCode = $this->generateGiftCardCode(
                $this->batch->gift_card_length,
                $this->batch->prefix
            );

            Log::info("🪄 [Batch #{$this->batch->id}] Attempt {$attempt}/{$this->batch->gift_card_count} | Code: {$manualCode}");

            $maxRetries = 3;
            $retryDelay = 3;
            $response = null;

            for ($retry = 0; $retry < $maxRetries; $retry++) {
                try {
                    $response = $service->createGiftCard(
                        $this->batch->card_value,
                        $manualCode,
                        $this->batch->note
                    );

                    Log::info("📩 Shopify Response:", [
                        'batch_id' => $this->batch->id,
                        'response' => $response
                    ]);

                    // ✅ If throttled, wait and retry
                    if (isset($response['errors']) && $this->isRateLimitError($response['errors'])) {
                        Log::warning("⚠️ Shopify rate limit hit. Retrying after {$retryDelay}s...");
                        sleep($retryDelay);
                        continue;
                    }

                    break;
                } catch (Throwable $e) {
                    Log::error("❌ [Attempt {$attempt}] Exception: {$e->getMessage()}");
                    if ($retry < $maxRetries - 1) {
                        sleep($retryDelay);
                        continue;
                    }
                    $response = ['errors' => [$e->getMessage()]];
                }
            }

            // === Handle results ===
            if (isset($response['errors']) && !empty($response['errors'])) {
                // ❌ Failed card
                GeneratedGiftCard::create([
                    'batch_id' => $this->batch->id,
                    'code' => $manualCode,
                    'status' => 'failed',
                    'balance' => $this->batch->card_value,
                    'error_message' => json_encode($response['errors']),
                ]);
                $failureCount++;
            } else {
                // ✅ Success card
                $giftCardId = $response['giftCardId'] ?? null;
                $giftCardCode = $response['giftCardCode'] ?? $manualCode;
                $balance = $response['balance'] ?? $this->batch->card_value;

                GeneratedGiftCard::create([
                    'batch_id' => $this->batch->id,
                    'code' => $giftCardCode,
                    'shopify_giftcard_id' => $giftCardId,
                    'balance' => $balance,
                    'status' => 'created',
                    'error_message' => null,
                ]);
                $successCount++;
            }

            // Small delay to avoid API throttling
            usleep(600000);
        }

        // === Final batch status ===
        $finalStatus = match (true) {
            $successCount === $this->batch->gift_card_count => 'success',
            $successCount > 0 && $failureCount > 0 => 'partial_failed',
            default => 'failed'
        };

        $this->batch->update([
            'status' => $finalStatus,
            'completed_at' => now(),
        ]);

        Log::info("🏁 Batch Complete — ✅ {$successCount} success, ❌ {$failureCount} failed");

        // ✅ Generate and send Excel report
        $this->sendExcelReport();
    }

    private function generateGiftCardCode(int $length, ?string $prefix = null): string
    {
        $prefix = strtoupper($prefix ?? '');
        $randomPartLength = max(1, $length - strlen($prefix)); // ✅ prevent negative or 0
        $randomPart = strtoupper(substr(bin2hex(random_bytes(ceil($randomPartLength / 2))), 0, $randomPartLength));
        return $prefix . $randomPart;
    }
private function sendExcelReport()
{
    try {
        $fileName = "gift_cards_batch_{$this->batch->id}.xlsx";
        $filePath = storage_path("app/public/{$fileName}");

        $cards = GeneratedGiftCard::where('batch_id', $this->batch->id)
            ->select('code', 'shopify_giftcard_id', 'status', 'balance', 'error_message')
            ->get();

        // ✅ Force storage on 'public' disk
        Excel::store(new GiftCardsExport($cards), $fileName, 'public');

        Log::info("📊 Excel report generated at: {$filePath}");

        $emails = json_decode($this->batch->email_list, true) ?? [];
        $emails = array_filter($emails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

        if (!empty($emails)) {
            Mail::to($emails)->send(new GiftCardBatchReportMail($filePath, $this->batch->id));
            Log::info("📧 Gift card batch #{$this->batch->id} Excel sent to: " . implode(', ', $emails));

          
        } else {
            Log::warning("⚠️ No valid email addresses found for batch #{$this->batch->id}");
        }
    } catch (Throwable $e) {
        Log::error("❌ Failed to send Excel report for batch #{$this->batch->id}: {$e->getMessage()}");
    }
}


    private function isRateLimitError($errors)
    {
        if (!is_iterable($errors)) return false;
        foreach ($errors as $error) {
            if (is_array($error) && isset($error['message']) && str_contains(strtolower($error['message']), 'throttled')) {
                return true;
            }
        }
        return false;
    }
}
