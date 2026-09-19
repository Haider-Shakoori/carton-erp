<?php
// database/migrations/2024_01_01_000000_fix_bom_division_factor.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Fix existing BOMs with wrong division factor
        // The client's Excel uses division factor of ~319764, not 10152765
        DB::table('boms')
            ->where('division_factor', 10152765)
            ->update(['division_factor' => 319764]);
    }

    public function down()
    {
        // Revert if needed
        DB::table('boms')
            ->where('division_factor', 319764)
            ->update(['division_factor' => 10152765]);
    }
};
