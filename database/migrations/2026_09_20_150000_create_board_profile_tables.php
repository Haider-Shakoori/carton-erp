<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Board profiles describe the manufacturing recipe of a corrugated board
     * (ply, flute, per-layer paper recipe) independently of any carton size.
     * Carton specifications resolve a profile into technical BOM rows.
     */
    public function up(): void
    {
        if (! Schema::hasTable('board_profiles')) {
            Schema::create('board_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 64)->unique();
                $table->text('description')->nullable();
                $table->unsignedInteger('ply')->default(5);
                $table->string('flute_type', 20)->nullable();
                $table->decimal('wastage_percentage', 5, 2)->default(5);
                $table->boolean('is_active')->default(true);
                $table->string('version', 20)->default('1.0');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'ply']);
            });
        }

        if (! Schema::hasTable('board_profile_layers')) {
            Schema::create('board_profile_layers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('board_profile_id')->constrained('board_profiles')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                // outer_liner | flute | center_liner | inner_liner | generic_client_formula
                $table->string('role', 40)->default('generic_client_formula');
                // paper | adhesive | printing | auxiliary
                $table->string('component_type', 20)->default('paper');
                $table->foreignId('material_id')->nullable()->constrained('products')->nullOnDelete();
                $table->unsignedInteger('gsm')->nullable();
                $table->decimal('multiplication_layer', 8, 4)->default(1);
                $table->string('flute_type', 20)->nullable();
                $table->decimal('take_up_factor', 8, 4)->nullable();
                $table->boolean('commercial_work_enabled')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['board_profile_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('board_profile_layers');
        Schema::dropIfExists('board_profiles');
    }
};
