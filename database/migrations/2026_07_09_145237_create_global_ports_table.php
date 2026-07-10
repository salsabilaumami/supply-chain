<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')
                ->nullable()
                ->constrained('countries')
                ->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('city')->nullable();
            $table->string('type')->default('Seaport');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('capacity_score', 8, 2)->default(0);
            $table->decimal('congestion_score', 8, 2)->default(0);
            $table->decimal('weather_exposure_score', 8, 2)->default(0);
            $table->decimal('risk_score', 8, 2)->default(0);
            $table->string('risk_level')->default('low');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_ports');
    }
};