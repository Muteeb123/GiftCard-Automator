<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    /**
     * Create a recurring Shopify charge for the selected plan.
     */
    public function createCharge(Request $request)
    {
        $planId = $request->plan_id;
        Log::info("💳 Creating charge for plan ID: {$planId}");

        $shop = auth()->user(); 
        $plan = Plan::findOrFail($planId);

        try {
            // Create recurring application charge
            $charge = $shop->api()->rest('POST', '/admin/api/2025-01/recurring_application_charges.json', [
                'recurring_application_charge' => [
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'return_url' => route('billing.confirm'),
                    'trial_days' => $plan->trial_days,
                    'test' => true, // ⚠️ true = test mode; false = real charge
                ],
            ]);

            Log::info('Shopify Charge Response:', $charge['body']);

            $body = $charge['body'] ?? [];

            // Check if the response contains the expected key
            if (!isset($body['recurring_application_charge'])) {
                Log::error('❌ Shopify charge creation failed', $body);
                return redirect()->back()->with('error', 'Failed to create charge. Check logs.');
            }

            $confirmationUrl = $body['recurring_application_charge']['confirmation_url'];
            return redirect($confirmationUrl);

        } catch (\Exception $e) {
            Log::error("❌ Exception creating Shopify charge: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Exception occurred while creating charge.');
        }
    }

    /**
     * Confirm and activate a Shopify charge after merchant approval.
     */
    public function confirm(Request $request)
    {
        $shop = auth()->user();
        $chargeId = $request->charge_id;

        if (!$chargeId) {
            Log::error('❌ Charge ID missing in confirm callback', $request->all());
            return redirect()->route('home')->with('error', 'Charge ID missing.');
        }

        try {
            // Get the charge status
            $response = $shop->api()->rest('GET', "/admin/api/2025-01/recurring_application_charges/{$chargeId}.json");
            $charge = $response['body']['recurring_application_charge'] ?? null;

            if (!$charge) {
                Log::error('❌ Failed to retrieve charge from Shopify', $response['body'] ?? []);
                return redirect()->route('home')->with('error', 'Failed to retrieve charge info.');
            }

            if ($charge['status'] === 'accepted') {
                // Activate the charge
                $shop->api()->rest('POST', "/admin/api/2025-01/recurring_application_charges/{$chargeId}/activate.json");

                // Store the charge ID in the shop record
                $shop->plan_id = $chargeId;
                $shop->save();

                Log::info("✅ Shopify charge activated: {$chargeId}");
                return redirect()->route('home')->with('success', 'Plan activated successfully!');
            }

            Log::warning("⚠️ Shopify charge not accepted", $charge);
            return redirect()->route('home')->with('error', 'Charge not accepted.');

        } catch (\Exception $e) {
            Log::error("❌ Exception confirming Shopify charge: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('home')->with('error', 'Exception occurred while confirming charge.');
        }
    }
}
