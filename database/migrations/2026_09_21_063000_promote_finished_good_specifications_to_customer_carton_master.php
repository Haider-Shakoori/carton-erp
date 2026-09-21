<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promote the existing legacy finished-good specification table into the
     * customer-specific carton master used by the client workbook import.
     *
     * Historical rates are retained only as reference text. Live quotation
     * pricing continues to come from the canonical carton/BOM costing flow.
     */
    public function up(): void
    {
        Schema::table('finished_good_specifications', function (Blueprint $table) {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('product_id')
                ->constrained('accounts')
                ->nullOnDelete();

            $table->string('source_key', 191)
                ->nullable()
                ->unique()
                ->after('customer_id');

            $table->unsignedInteger('source_row')
                ->nullable()
                ->after('source_key');

            $table->string('source_customer_label')
                ->nullable()
                ->after('source_row');

            $table->string('source_size_raw', 500)
                ->nullable()
                ->after('source_customer_label');

            $table->string('source_unit', 20)
                ->nullable()
                ->after('source_size_raw');

            $table->string('source_layer_raw', 100)
                ->nullable()
                ->after('source_unit');

            $table->unsignedTinyInteger('ply')
                ->nullable()
                ->after('flute_type');

            $table->text('print_spec')
                ->nullable()
                ->after('finish_type');

            $table->string('reel_cut', 255)
                ->nullable()
                ->after('print_spec');

            $table->string('pack_description', 255)
                ->nullable()
                ->after('reel_cut');

            $table->text('historical_rate_note')
                ->nullable()
                ->after('unit_price');

            $table->text('source_remark')
                ->nullable()
                ->after('historical_rate_note');

            $table->text('source_extra')
                ->nullable()
                ->after('source_remark');

            $table->index(['customer_id', 'product_id'], 'fg_spec_customer_product_idx');
        });
    }

    public function down(): void
    {
        Schema::table('finished_good_specifications', function (Blueprint $table) {
            $table->dropIndex('fg_spec_customer_product_idx');
            $table->dropForeign(['customer_id']);
            $table->dropUnique(['source_key']);
            $table->dropColumn([
                'customer_id',
                'source_key',
                'source_row',
                'source_customer_label',
                'source_size_raw',
                'source_unit',
                'source_layer_raw',
                'ply',
                'print_spec',
                'reel_cut',
                'pack_description',
                'historical_rate_note',
                'source_remark',
                'source_extra',
            ]);
        });
    }
};
