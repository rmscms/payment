<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 50);
            $table->string('order_id')->nullable();
            $table->string('authority')->nullable()->index();
            $table->string('reference_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('status', 30)->default('initialized')->index();
            $table->string('status_detail')->nullable();
            $table->string('card_pan')->nullable();
            $table->string('card_hash')->nullable();
            $table->json('metadata')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};

