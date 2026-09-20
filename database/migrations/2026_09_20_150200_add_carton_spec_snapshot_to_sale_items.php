<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Freeze the complete commercial and technical carton specification on the
     * sale item when a quotation is built or accepted. Historical orders must
     * remain reproducible after board profile, config or landed-rate changes.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sale_items', 'carton_spec_snapshot')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->longText('carton_spec_snapshot')->nullable()->after('manual_bom_snapshot');
            });
        }
    }

    public function down(): void
    {
        // Non-destructive: frozen historical pricing depends on this snapshot.
    }
};
