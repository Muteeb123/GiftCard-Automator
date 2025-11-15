<?php

use App\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GiftCardBatchController;
use App\Http\Controllers\PlanController;

Route::group(['middleware' => ['verify.embedded', 'verify.shopify']], function () {

    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/createGiftcardBatch', [GiftCardBatchController::class, 'createPage'])->name('create.giftcard.batch');
    Route::get('/search', [DashboardController::class, 'orderSeacrhfilter'])->name('search');
      Route::get('/giftcards', [GiftCardBatchController::class, 'index'])->name('giftcards.index');
       Route::get('/giftcardPage', [GiftCardBatchController::class, 'page'])->name('giftcards.page');
Route::post('/giftcards', [GiftCardBatchController::class, 'store'])->name('giftcards.store');
    Route::get('/plans',[PlanController::class,'index'])->name('plans.index');
    Route::get('/giftcards/{batch}/logs', [GiftCardBatchController::class, 'show'])->name('giftcards.logs');
    Route::get('/giftcard-logs/{batch}', [GiftCardBatchController::class, 'logsPage'])
    ->name('giftcards.logs.page');
     Route::post('/billing/create', [BillingController::class, 'createCharge'])->name('billing.create');
    Route::get('/billing/confirm', [BillingController::class, 'confirm'])->name('billing.confirm');

    Route::post('/plans/subscribe', [PlanController::class,'subscribe'])->name('plans.subscribe');
});
  Route::get('/giftcards/download/{id}', [GiftCardBatchController::class, 'download'])
    ->name('giftcards.download');
require __DIR__ . '/auth.php';
