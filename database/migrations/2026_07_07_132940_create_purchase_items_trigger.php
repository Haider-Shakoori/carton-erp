<?php
// database/migrations/2026_07_07_000002_create_purchase_items_trigger.php

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
        DB::unprepared('DROP TRIGGER IF EXISTS before_purchase_items_update');
        DB::unprepared('DROP TRIGGER IF EXISTS before_purchase_items_insert');

        // Create BEFORE UPDATE trigger
        DB::unprepared("
            CREATE TRIGGER before_purchase_items_update
            BEFORE UPDATE ON purchase_items
            FOR EACH ROW
            BEGIN
                SET NEW.qty_available = NEW.qty - COALESCE(NEW.qty_sold, 0) - COALESCE(NEW.qty_used, 0) - COALESCE(NEW.qty_wasted, 0);
            END
        ");

        // Create BEFORE INSERT trigger
        DB::unprepared("
            CREATE TRIGGER before_purchase_items_insert
            BEFORE INSERT ON purchase_items
            FOR EACH ROW
            BEGIN
                SET NEW.qty_available = NEW.qty - COALESCE(NEW.qty_sold, 0) - COALESCE(NEW.qty_used, 0) - COALESCE(NEW.qty_wasted, 0);
            END
        ");
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS before_purchase_items_update');
        DB::unprepared('DROP TRIGGER IF EXISTS before_purchase_items_insert');
    }
};
