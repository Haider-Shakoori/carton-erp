<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_reel_consumptions', function (Blueprint $table) {
            $table->string('allocation_method', 30)
                ->default('fifo')
                ->after('after_weight_kg');
            $table->unsignedBigInteger('selected_by')
                ->nullable()
                ->after('allocation_method');
            $table->decimal('declared_final_weight_kg', 18, 4)
                ->nullable()
                ->after('selected_by');
            $table->text('selection_note')
                ->nullable()
                ->after('declared_final_weight_kg');

            $table->foreign('selected_by', 'prc_selected_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->index(
                ['allocation_method', 'consumed_at'],
                'prc_allocation_method_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('production_reel_consumptions', function (Blueprint $table) {
            $table->dropForeign('prc_selected_by_fk');
            $table->dropIndex('prc_allocation_method_idx');
            $table->dropColumn([
                'allocation_method',
                'selected_by',
                'declared_final_weight_kg',
                'selection_note',
            ]);
        });
    }
};
