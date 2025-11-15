<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PlanController extends Controller
{
    //

    public function Index(){
        return Inertia::render('Plans');
    }

    public function subscribe(Request $request){
        $user = $request->user();
        $planType = $request->input('plan');

        // Validate plan type
        $validPlans = ['starter', 'growth', 'pro'];
$planType = strtolower($planType);

if (!in_array($planType, $validPlans)) {
    return response()->json(['error' => 'Invalid plan type.'], 400);
}

        // Update or create the user's plan
        $user->userplan()->updateOrCreate(
            ['user_id' => $user->id],
            ['plan_type' => $planType]
        );
        Log::info("User ID {$user->id} subscribed to the {$planType} plan.");
        
        return response()->json(['message' => 'Plan subscribed successfully.']);
    }
}
