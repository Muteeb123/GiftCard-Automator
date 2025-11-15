<?php

namespace App\Http\Controllers;

use App\Jobs\CreateGiftCards;
use App\Jobs\MarkExpiredGiftCardsJob;
use Illuminate\Http\Request;
use App\Models\GiftCardBatch;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class GiftCardBatchController extends Controller
{
    /**
     * Store a new gift card batch request.
     */
   public function store(Request $request)
{
    Log::info("🎁 Received request to create gift card batch", $request->all());

    $validator = Validator::make($request->all(), [
        'card_value'        => 'required|numeric|min:1',
        'gift_card_count'   => 'required|integer|min:1',
        'gift_card_length'  => 'required|integer|min:4|max:30',
        'gift_card_expiry'  => 'nullable|date|after:today',
        'prefix'            => 'nullable|string|max:50',
        'email_list'        => 'nullable|array',
        'email_list.*'      => 'nullable|email',
        'note'              => 'nullable|string|max:1000',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    $batch = GiftCardBatch::create([
        'user_id'           => Auth::id(),
        'card_value'        => $request->card_value,
        'gift_card_count'   => $request->gift_card_count,
        'gift_card_length'  => $request->gift_card_length,
        'gift_card_expiry'  => $request->gift_card_expiry,
        'prefix'            => $request->prefix,
        'email_list'        => $request->email_list ? json_encode($request->email_list) : null,
        'note'              => $request->note,
        'status'            => 'pending',
    ]);

    CreateGiftCards::dispatch($batch);

    return response()->json([
        'status' => 'success',
        'message' => 'Gift card batch created successfully.',
        'batch_id' => $batch->id,
    ]);
}


    /**
     * Fetch list of all batches for the current user (optional endpoint)
     */

    public function page(){
        return Inertia::render('GiftCardBatchList');
    }
 public function index()
{
    $batches = GiftCardBatch::where('user_id', Auth::id())
        ->orderBy('created_at', 'desc')
        ->paginate(10); // 10 per page
      foreach ($batches as $batch) {
        if ($batch->gift_card_expiry) {
            $expiryDate = Carbon::parse($batch->gift_card_expiry);

            if (now()->greaterThanOrEqualTo($expiryDate) && $batch->status !== 'expired') {
                Log::info(" Batch #{$batch->id} reached expiry date, dispatching ExpireGiftCardsJob...");
                MarkExpiredGiftCardsJob::dispatch($batch->id);
            }
        }
    }
    Log::info('🎁 Fetched paginated gift card batches', ['count' => $batches->total()]);
    return response()->json($batches);
}

    /**
     * View a specific batch and its generated cards (optional)
     */

  
public function logsPage($batchId)
{   
    $batch = GiftCardBatch::findOrfail($batchId);
    return Inertia::render('Logs', [
        'batchId' => $batch,
    ]);
}
    public function show($id)
    {
      $batches = GiftCardBatch::where('user_id', Auth::id())->findOrFail($id);

$batch = $batches->generatedGiftCards()->paginate(10);

Log::info('🎁 Fetched gift card batch details', [
    'batch_id' => $id,
    'gift_card_count' => $batch->total(),
]);

        return response()->json($batch);
    }

     public function createPage()
{
    $user = Auth::user();


    return Inertia::render('CreateGiftCardBatch');
}

public function download($id)
{
    $batch = GiftCardBatch::findOrFail($id);

    // Match how you saved it
    $fileName = "gift_cards_batch_{$batch->id}.xlsx";
    $filePath = storage_path("app/public/{$fileName}");

    if (!file_exists($filePath)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    return response()->download($filePath, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}
}
