<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Exception;

class ShopifyGiftCardService
{
    protected $apiUrl;
    protected $accessToken;

    /**
     * Initialize with user's store and token.
     */
    public function __construct(User $user)
    {
        // 🧹 Clean store name — fix double .myshopify.com issue
        $store = preg_replace('/(\.myshopify\.com)+$/', '.myshopify.com', $user->name);

        $this->apiUrl = "https://{$store}/admin/api/2025-01/graphql.json";
        Log::info("🔗 Shopify API URL set to: {$store}");
        $this->accessToken = $user->password; // Shopify access token
    }

    /**
     * Create a Shopify Gift Card.
     *
     * @param float|int $value
     * @param string|null $manualCode
     * @param string|null $note
     * @param string|null $expiresOn (YYYY-MM-DD)
     * @return array
     */
    public function createGiftCard($value, $manualCode = null, $note = null, $expiresOn = null)
    {
        // ✅ Correct GraphQL mutation — same as your working one
        $mutation = <<<'GQL'
            mutation CreateGiftCard($input: GiftCardCreateInput!) {
                giftCardCreate(input: $input) {
                    giftCard {
                        id
                        note
                        expiresOn
                        initialValue {
                            amount
                            currencyCode
                        }
                    }
                    giftCardCode
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;

        // 🧩 Prepare input (NO currencyCode — Shopify uses store default)
        $input = [
            'initialValue' => (string)$value,
        ];

        if (!empty($manualCode)) {
            $input['code'] = $manualCode; // manual code if provided
        }
        if (!empty($note)) {
            $input['note'] = $note;
        }
        if (!empty($expiresOn)) {
            $input['expiresOn'] = $expiresOn;
        }

        try {
            // 🧵 Prevent hitting Shopify rate limits
            usleep(500000); // 0.5s delay
            Log::info('Input: ' . json_encode($input));
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'query' => $mutation,
                'variables' => ['input' => $input],
            ]);

            if ($response->failed()) {
                Log::error('❌ Shopify GiftCardCreate request failed', [
                    'url' => $this->apiUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'giftCardId' => null,
                    'giftCardCode' => null,
                    'errors' => ['HTTP Request Failed: ' . $response->status()],
                ];
            }

            $data = $response->json();
            Log::info('📩 Shopify GiftCardCreate Response ' . json_encode($data));
            $giftCardCreate = $data['data']['giftCardCreate'] ?? [];

            $giftCard = $giftCardCreate['giftCard'] ?? null;
            $giftCardCode = $giftCardCreate['giftCardCode'] ?? null;
            $userErrors = $giftCardCreate['userErrors'] ?? [];

            // 🧾 Log everything for debugging
            Log::info('📩 Shopify GiftCardCreate Response', [
                'giftCard' => $giftCard,
                'giftCardCode' => $giftCardCode,
                'userErrors' => $userErrors,
            ]);

            if (!empty($userErrors)) {
                Log::warning('⚠️ Shopify GiftCardCreate returned user errors', [
                    'errors' => $userErrors,
                ]);

                return [
                    'giftCardId' => null,
                    'giftCardCode' => null,
                    'errors' => $userErrors,
                ];
            }

            return [
                'giftCardId' => $giftCard['id'] ?? null,
                'giftCardCode' => $giftCardCode ?? null,
                'errors' => [],
            ];
        } catch (Exception $e) {
            Log::error('💥 Exception while creating Gift Card', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'giftCardId' => null,
                'giftCardCode' => null,
                'errors' => [$e->getMessage()],
            ];
        }
    }
}
