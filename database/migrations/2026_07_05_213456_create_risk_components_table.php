<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_score_id')->constrained()->cascadeOnDelete();
            $table->string('component_name', 50);
            $table->decimal('raw_value', 20, 4)->nullable();
            $table->decimal('normalized_score', 5, 2)->default(0);
            $table->decimal('weight', 5, 2);
            $table->decimal('weighted_score', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['risk_score_id', 'component_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_components');
    }
};