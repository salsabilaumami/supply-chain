<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positive_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 100)->unique();
            $table->string('language', 10)->default('en');
            $table->decimal('weight', 5, 2)->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positive_words');
    }
};