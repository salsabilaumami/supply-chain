<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_sentiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_cache_id')->constrained('news_caches')->cascadeOnDelete();
            $table->unsignedInteger('positive_score')->default(0);
            $table->unsignedInteger('negative_score')->default(0);
            $table->unsignedInteger('neutral_score')->default(0);
            $table->enum('sentiment', ['positive', 'neutral', 'negative']);
            $table->decimal('risk_score', 5, 2)->default(0);
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->unique('news_cache_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_sentiments');
    }
};