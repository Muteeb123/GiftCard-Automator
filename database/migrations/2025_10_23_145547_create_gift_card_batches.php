<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_batches', function (Blueprint $table) {
            $table->id();

            // Foreign key to user (Shopify store or app user)
            $table->unsignedBigInteger('user_id')->index();

            // Basic batch details
            $table->decimal('card_value', 10, 2);
            $table->integer('gift_card_count');
            $table->integer('gift_card_length');
            $table->date('gift_card_expiry')->nullable();
            $table->string('prefix', 50)->nullable();
            $table->json('email_list')->nullable();
            $table->text('note')->nullable();

            // Status tracking
            $table->enum('status', [
                'pending',
                'in_progress',
                'success',
                'partial_failed',
                'failed'
            ])->default('pending');

            $table->timestamps();

            // Foreign key constraint (optional but good)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_batches');
    }
};
