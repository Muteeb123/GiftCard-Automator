<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_gift_cards', function (Blueprint $table) {
            $table->id();

            // Link to batch
            $table->unsignedBigInteger('batch_id')->index();

            // Gift card details
            $table->string('code')->unique();
            $table->string('shopify_giftcard_id')->nullable();
            $table->enum('status', ['created', 'failed','used','expired'])->default('created');
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('batch_id')
                  ->references('id')
                  ->on('gift_card_batches')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_gift_cards');
    }
};
