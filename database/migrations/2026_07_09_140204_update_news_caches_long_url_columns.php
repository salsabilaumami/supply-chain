<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE news_caches MODIFY image_url TEXT NULL');
        DB::statement('ALTER TABLE news_caches MODIFY author TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE news_caches MODIFY image_url VARCHAR(255) NULL');
        DB::statement('ALTER TABLE news_caches MODIFY author VARCHAR(255) NULL');
    }
};