<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->unique();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
            $table->string('documentation_url')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_drivers');
    }
};

