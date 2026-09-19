<?php
// database/migrations/2026_07_07_000000_add_trigger_to_purchase_items.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Drop triggers if they exist
        DB::unprepared('DROP TRIGGER IF EXISTS update_qty_available_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS update_qty_available_before_insert');

        // Create BEFORE UPDATE trigger
        DB::unprepared('
            CREATE TRIGGER update_qty_available_before_update
            BEFORE UPDATE ON purchase_items
            FOR EACH ROW
            BEGIN
                SET NEW.qty_available = NEW.qty - IFNULL(NEW.qty_sold, 0) - IFNULL(NEW.qty_used, 0) - IFNULL(NEW.qty_wasted, 0);
            END
        ');

        // Create BEFORE INSERT trigger
        DB::unprepared('
            CREATE TRIGGER update_qty_available_before_insert
            BEFORE INSERT ON purchase_items
            FOR EACH ROW
            BEGIN
                SET NEW.qty_available = NEW.qty - IFNULL(NEW.qty_sold, 0) - IFNULL(NEW.qty_used, 0) - IFNULL(NEW.qty_wasted, 0);
            END
        ');
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS update_qty_available_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS update_qty_available_before_insert');
    }
};
