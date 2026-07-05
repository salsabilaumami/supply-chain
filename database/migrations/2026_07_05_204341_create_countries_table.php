<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('official_name', 150)->nullable();
            $table->char('iso2_code', 2)->unique();
            $table->char('iso3_code', 3)->unique();
            $table->string('capital', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('subregion', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->string('currency_name', 100)->nullable();
            $table->string('currency_symbol', 20)->nullable();
            $table->unsignedBigInteger('population')->nullable();
            $table->text('flag_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};