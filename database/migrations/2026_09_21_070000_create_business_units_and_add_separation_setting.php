<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('icon', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('business_units')->insert([
            [
                'code' => '3d_carton',
                'name' => '3D Carton',
                'icon' => 'bi-box-seam',
                'description' => '3D corrugated carton business operations.',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'syrup_pack',
                'name' => 'Syrup Pack',
                'icon' => 'bi-box2-heart',
                'description' => 'Syrup packaging business operations.',
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('separate_business_units_enabled')
                ->default(false)
                ->after('currency');
            $table->foreignId('default_business_unit_id')
                ->nullable()
                ->after('separate_business_units_enabled')
                ->constrained('business_units')
                ->nullOnDelete();
        });

        $defaultBusinessUnitId = DB::table('business_units')
            ->where('code', '3d_carton')
            ->value('id');

        DB::table('settings')
            ->whereNull('default_business_unit_id')
            ->update(['default_business_unit_id' => $defaultBusinessUnitId]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_business_unit_id');
            $table->dropColumn('separate_business_units_enabled');
        });

        Schema::dropIfExists('business_units');
    }
};
