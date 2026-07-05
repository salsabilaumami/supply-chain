<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->char('base_currency', 3);
            $table->char('target_currency', 3);
            $table->decimal('rate', 20, 8);
            $table->decimal('change_percentage', 10, 4)->nullable();
            $table->decimal('currency_risk', 5, 2)->default(0);
            $table->timestamp('recorded_at');
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['country_id', 'recorded_at']);
            $table->index(['base_currency', 'target_currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};