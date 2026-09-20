<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_items', 'qty_adjusted')) {
                $table->decimal('qty_adjusted', 18, 6)->default(0)->after('qty_wasted')
                    ->comment('Signed native-unit inventory reconciliation adjustment.');
            }

            if (! Schema::hasColumn('purchase_items', 'qty_kg_adjusted')) {
                $table->decimal('qty_kg_adjusted', 18, 6)->default(0)->after('qty_kg_wasted')
                    ->comment('Signed kg inventory reconciliation adjustment for roll-based stock.');
            }
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            foreach ([
                'before_purchase_items_update',
                'before_purchase_items_insert',
                'update_qty_available_before_update',
                'update_qty_available_before_insert',
                'update_purchase_item_qty_before_update',
                'update_purchase_item_qty_before_insert',
                'reconcile_purchase_items_available_before_update',
                'reconcile_purchase_items_available_before_insert',
            ] as $trigger) {
                DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
            }

            DB::unprepared('
                CREATE TRIGGER reconcile_purchase_items_available_before_update
                BEFORE UPDATE ON purchase_items
                FOR EACH ROW
                BEGIN
                    SET NEW.qty_available =
                        NEW.qty
                        - COALESCE(NEW.qty_sold, 0)
                        - COALESCE(NEW.qty_used, 0)
                        - COALESCE(NEW.qty_wasted, 0)
                        + COALESCE(NEW.qty_adjusted, 0);
                END
            ');

            DB::unprepared('
                CREATE TRIGGER reconcile_purchase_items_available_before_insert
                BEFORE INSERT ON purchase_items
                FOR EACH ROW
                BEGIN
                    SET NEW.qty_available =
                        NEW.qty
                        - COALESCE(NEW.qty_sold, 0)
                        - COALESCE(NEW.qty_used, 0)
                        - COALESCE(NEW.qty_wasted, 0)
                        + COALESCE(NEW.qty_adjusted, 0);
                END
            ');
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::unprepared('DROP TRIGGER IF EXISTS reconcile_purchase_items_available_before_update');
            DB::unprepared('DROP TRIGGER IF EXISTS reconcile_purchase_items_available_before_insert');

            DB::unprepared('
                CREATE TRIGGER update_purchase_item_qty_before_update
                BEFORE UPDATE ON purchase_items
                FOR EACH ROW
                BEGIN
                    SET NEW.qty_available =
                        NEW.qty
                        - COALESCE(NEW.qty_sold, 0)
                        - COALESCE(NEW.qty_used, 0)
                        - COALESCE(NEW.qty_wasted, 0);
                END
            ');

            DB::unprepared('
                CREATE TRIGGER update_purchase_item_qty_before_insert
                BEFORE INSERT ON purchase_items
                FOR EACH ROW
                BEGIN
                    SET NEW.qty_available =
                        NEW.qty
                        - COALESCE(NEW.qty_sold, 0)
                        - COALESCE(NEW.qty_used, 0)
                        - COALESCE(NEW.qty_wasted, 0);
                END
            ');
        }

        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'qty_kg_adjusted')) {
                $table->dropColumn('qty_kg_adjusted');
            }
            if (Schema::hasColumn('purchase_items', 'qty_adjusted')) {
                $table->dropColumn('qty_adjusted');
            }
        });
    }
};
