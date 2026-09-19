<?php
// database/migrations/2026_07_08_000000_add_trigger_to_update_purchase_item_qty.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Drop existing triggers
        DB::unprepared('DROP TRIGGER IF EXISTS update_purchase_item_qty_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS update_purchase_item_qty_before_insert');

        // Create BEFORE UPDATE trigger
        DB::unprepared('
            CREATE TRIGGER update_purchase_item_qty_before_update
            BEFORE UPDATE ON purchase_items
            FOR EACH ROW
            BEGIN
                SET NEW.qty_available = NEW.qty - COALESCE(NEW.qty_sold, 0) - COALESCE(NEW.qty_used, 0) - COALESCE(NEW.qty_wasted, 0);
            END
        ');

        // Create BEFORE INSERT trigger
        DB::unprepared('
            CREATE TRIGGER update_purchase_item_qty_before_insert
            BEFORE INSERT ON purchase_items
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

        DB::unprepared('DROP TRIGGER IF EXISTS update_purchase_item_qty_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS update_purchase_item_qty_before_insert');
    }
};
