<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist the manual-BOM formula snapshot on sale items so post-save
     * reporting (commercial paper/work/profit and physical estimate) can be
     * reconstructed from the EXACT manual inputs used at quotation time,
     * instead of falling back to the template BOM rows.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->longText('manual_bom_snapshot')->nullable()->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('manual_bom_snapshot');
        });
    }
};
