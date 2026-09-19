<?php
// database/migrations/2026_07_18_000001_add_exchange_rate_to_boms.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('boms', function (Blueprint $table) {
            // Add exchange rate field (USD to AFN)
            if (!Schema::hasColumn('boms', 'exchange_rate')) {
                $table->decimal('exchange_rate', 15, 4)->default(85)->after('profit_margin_percentage');
            }

            // Add timestamp for when exchange rate was last updated
            if (!Schema::hasColumn('boms', 'exchange_rate_updated_at')) {
                $table->timestamp('exchange_rate_updated_at')->nullable()->after('exchange_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'exchange_rate_updated_at']);
        });
    }
};
