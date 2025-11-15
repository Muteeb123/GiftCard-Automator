<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedGiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'code',
        'status',
        'shopify_giftcard_id',  // optional, if you want to store Shopify's returned ID
        'error_message',    // optional, if failed
        'balance',         
    ];

    /**
     * Relationship: A GeneratedGiftCard belongs to one GiftCardBatch
     */
    public function batch()
    {
        return $this->belongsTo(GiftCardBatch::class, 'batch_id');
    }
}
