<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'length_inch' => fn (Blueprint $table) => $table->decimal('length_inch', 10, 2)->nullable(),
            'width_inch' => fn (Blueprint $table) => $table->decimal('width_inch', 10, 2)->nullable(),
            'height_inch' => fn (Blueprint $table) => $table->decimal('height_inch', 10, 2)->nullable(),
            'reel_length_inch' => fn (Blueprint $table) => $table->decimal('reel_length_inch', 10, 2)->nullable(),
            'reel_height_inch' => fn (Blueprint $table) => $table->decimal('reel_height_inch', 10, 2)->nullable(),
            'paper_gsm' => fn (Blueprint $table) => $table->unsignedInteger('paper_gsm')->nullable(),
            'layers' => fn (Blueprint $table) => $table->unsignedInteger('layers')->nullable(),
            'division_factor' => fn (Blueprint $table) => $table->decimal('division_factor', 15, 8)->nullable(),
            'cut_length_inch' => fn (Blueprint $table) => $table->decimal('cut_length_inch', 10, 2)->nullable(),
            'cut_width_inch' => fn (Blueprint $table) => $table->decimal('cut_width_inch', 10, 2)->nullable(),
            'grh' => fn (Blueprint $table) => $table->unsignedInteger('grh')->nullable(),
            'ply' => fn (Blueprint $table) => $table->unsignedInteger('ply')->nullable(),
            'print' => fn (Blueprint $table) => $table->decimal('print', 15, 4)->default(0),
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('bom_items', $name)) {
                Schema::table('bom_items', $definition);
            }
        }
    }

    public function down(): void
    {
        // This migration repairs an incomplete historical schema transition.
        // Do not remove columns on rollback because upgraded installations may
        // have received them from older, environment-specific migrations.
    }
};
