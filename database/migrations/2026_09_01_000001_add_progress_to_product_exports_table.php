<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_exports', function (Blueprint $table) {
            $table->unsignedInteger('max_results')->nullable()->after('keyword_count');
            $table->unsignedInteger('keyword_index')->default(0)->after('max_results');
            $table->unsignedBigInteger('matched_rows')->default(0)->after('keyword_index');
            $table->string('current_keyword')->nullable()->after('matched_rows');
        });
    }

    public function down(): void
    {
        Schema::table('product_exports', function (Blueprint $table) {
            $table->dropColumn(['max_results', 'keyword_index', 'matched_rows', 'current_keyword']);
        });
    }
};
