<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economic_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('indicator_code', 50);
            $table->string('indicator_name', 100);
            $table->unsignedSmallInteger('year');
            $table->decimal('value', 25, 4)->nullable();
            $table->string('source', 100)->default('World Bank');
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['country_id', 'indicator_code', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('economic_indicators');
    }
};