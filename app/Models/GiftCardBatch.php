<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCardBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'card_value',
        'gift_card_count',
        'gift_card_length',
        'gift_card_expiry',
        'prefix',
        'email_list',
        'note',
        'status',
    ];

    protected $casts = [
        'email_list' => 'array',
        'gift_card_expiry' => 'datetime',
    ];

    /**
     * Relationship: A GiftCardBatch has many GeneratedGiftCards
     */
    public function generatedGiftCards()
    {
        return $this->hasMany(GeneratedGiftCard::class, 'batch_id');
    }

    /**
     * Optional: If you have a User model (e.g. Shopify store)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
