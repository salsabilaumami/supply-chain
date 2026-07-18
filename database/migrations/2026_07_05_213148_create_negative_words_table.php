<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negative_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 100);
            $table->string('language', 10)->default('en');
            $table->decimal('weight', 5, 2)->default(1);
            $table->timestamps();

            $table->unique(['word', 'language']);
            $table->index('word');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negative_words');
    }
};