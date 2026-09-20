<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make the commercial/physical classification of BOM rows explicit.
     *
     * component_type: paper | adhesive | printing | auxiliary
     * apply_work_percentage: null = legacy inference (paper-style rows get the
     * client 40% work/profit, adhesive rows do not). Existing rows are left
     * null so their historical behaviour is preserved exactly.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bom_items', 'component_type')) {
            Schema::table('bom_items', function (Blueprint $table) {
                $table->string('component_type', 20)->nullable()->after('unit');
            });
        }

        if (! Schema::hasColumn('bom_items', 'apply_work_percentage')) {
            Schema::table('bom_items', function (Blueprint $table) {
                $table->boolean('apply_work_percentage')->nullable()->after('work_percentage');
            });
        }
    }

    public function down(): void
    {
        // Non-destructive: the columns are additive and historical rows depend
        // on their classification for reporting.
    }
};
