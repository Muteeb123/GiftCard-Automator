<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use stdClass;

class OrdersPaidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shop's myshopify domain
     *
     * @var ShopDomain|string
     */
    public $shopDomain;

    /**
     * The webhook data
     *
     * @var object
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param string   $shopDomain The shop's myshopify domain.
     * @param stdClass $data       The webhook data (JSON decoded).
     *
     * @return void
     */
    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(IShopQuery $shopQuery)
    {
        try {
            // Convert domain and get shop
            $this->shopDomain = ShopDomain::fromNative($this->shopDomain);
            $shop = $shopQuery->getByDomain($this->shopDomain);

            // ✅ FIXED: Access $this->data as an object (stdClass)
            $orderId = $this->data->id ?? null;
            if (!$orderId) {
                Log::warning('⚠️ Missing order ID in webhook data.');
                return;
            }

            $shopifyOrderGID = "gid://shopify/Order/{$orderId}";

            // 1️⃣ Fetch order transaction details (to get gift card ID)
            $orderResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $shop->password,
            ])->post("https://{$this->shopDomain->toNative()}/admin/api/2025-01/graphql.json", [
                'query' => '
                    query OrderGiftCardDetails($id: ID!) {
                        order(id: $id) {
                            id
                            name
                            transactions {
                                id
                                gateway
                                status
                                amountSet {
                                    shopMoney {
                                        amount
                                        currencyCode
                                    }
                                }
                                receiptJson
                            }
                        }
                    }
                ',
                'variables' => ['id' => $shopifyOrderGID],
            ]);

            $transactions = $orderResponse['data']['order']['transactions'] ?? [];
            $giftCardId = null;

            foreach ($transactions as $txn) {
                if ($txn['gateway'] === 'gift_card' && !empty($txn['receiptJson'])) {
                    $receipt = json_decode($txn['receiptJson'], true);
                    $giftCardId = $receipt['gift_card_id'] ?? null;
                    break;
                }
            }

            if (!$giftCardId) {
                Log::info("ℹ️ No gift card transaction found for order {$orderId}");
                return;
            }

            // 2️⃣ Convert numeric gift card ID into full Shopify GID
            $giftCardGID = "gid://shopify/GiftCard/{$giftCardId}";

            // 3️⃣ Fetch gift card details
            $giftCardResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $shop->password,
            ])->post("https://{$this->shopDomain->toNative()}/admin/api/2025-01/graphql.json", [
                'query' => '
                    query GiftCardDetails($id: ID!) {
                        giftCard(id: $id) {
                            id
                            balance { amount }
                            initialValue { amount }
                            lastCharacters
                        }
                    }
                ',
                'variables' => ['id' => $giftCardGID],
            ]);

            $giftCard = $giftCardResponse['data']['giftCard'] ?? null;

            if (!$giftCard) {
                Log::error("❌ Gift card not found for GID: {$giftCardGID}");
                return;
            }

            $balance = $giftCard['balance']['amount'] ?? 0;

            // 4️⃣ Update your database using full GID (string match)
          $updateData = [
    'balance' => $balance,
    'updated_at' => now(),
];

// If balance is 0, mark as used
if ((float)$balance <= 0) {
    $updateData['status'] = 'used';
}

DB::table('generated_gift_cards')
    ->where('shopify_giftcard_id', $giftCardGID)
    ->update($updateData);
            Log::info("✅ Gift card {$giftCardGID} balance updated to {$balance}");

        } catch (\Exception $e) {
            Log::error("💥 Exception in OrdersPaidJob: " . $e->getMessage());
        }
    }
}
