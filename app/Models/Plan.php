<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

  
    protected $fillable = ['name', 'price', 'trial_days', 'shopify_plan_id'];

    /**
     * Relationship: A plan belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Optional helper methods for convenience.
     */
    public function isStarter(): bool
    {
        return $this->plan_type === 'starter';
    }

    public function isGrowth(): bool
    {
        return $this->plan_type === 'growth';
    }

    public function isPro(): bool
    {
        return $this->plan_type === 'pro';
    }
}
