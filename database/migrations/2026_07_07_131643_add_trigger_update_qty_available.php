<?php
// database/migrations/2026_07_07_000000_add_trigger_update_qty_available.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::unprepared('
            CREATE TRIGGER update_qty_available BEFORE UPDATE ON purchase_items
            FOR EACH ROW
            BEGIN
                SET NEW.qty_available = NEW.qty - COALESCE(NEW.qty_sold, 0) - COALESCE(NEW.qty_used, 0) - COALESCE(NEW.qty_wasted, 0);
            END
        ');
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS update_qty_available');
    }
};
