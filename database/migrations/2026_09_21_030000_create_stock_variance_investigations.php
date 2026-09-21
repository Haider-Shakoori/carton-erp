<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_variance_investigations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_adjustment_item_id')->unique();
            $table->string('status', 30)->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('due_date')->nullable();

            $table->string('root_cause_code', 80)->nullable();
            $table->text('root_cause_details')->nullable();
            $table->text('investigation_notes')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->unsignedBigInteger('opened_by')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('started_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('stock_adjustment_item_id', 'svi_adj_item_fk')
                ->references('id')->on('stock_adjustment_items')->cascadeOnDelete();
            $table->foreign('assigned_to', 'svi_assigned_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('opened_by', 'svi_opened_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('resolved_by', 'svi_resolved_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(['status', 'due_date'], 'svi_status_due_idx');
            $table->index('assigned_to', 'svi_assigned_idx');
            $table->index('opened_at', 'svi_opened_at_idx');
        });

        Schema::create('stock_variance_investigation_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_variance_investigation_id');
            $table->string('event_type', 50);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('stock_variance_investigation_id', 'svie_case_fk')
                ->references('id')->on('stock_variance_investigations')->cascadeOnDelete();
            $table->foreign('user_id', 'svie_user_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(
                ['stock_variance_investigation_id', 'created_at'],
                'svie_case_created_idx'
            );
        });

        // Preserve unresolved variances posted before this feature existed.
        $existing = DB::table('stock_adjustment_items as sai')
            ->join('stock_adjustments as sa', 'sa.id', '=', 'sai.stock_adjustment_id')
            ->where('sai.reason_code', 'unknown')
            ->select([
                'sai.id as adjustment_item_id',
                'sa.posted_by',
                'sa.posted_at',
            ])
            ->orderBy('sai.id')
            ->get();

        foreach ($existing as $row) {
            $openedAt = $row->posted_at ?: now();
            $investigationId = DB::table('stock_variance_investigations')->insertGetId([
                'stock_adjustment_item_id' => $row->adjustment_item_id,
                'status' => 'open',
                'assigned_to' => null,
                'due_date' => Carbon::parse($openedAt)->addDays(7)->toDateString(),
                'opened_by' => $row->posted_by,
                'opened_at' => $openedAt,
                'created_at' => $openedAt,
                'updated_at' => $openedAt,
            ]);

            DB::table('stock_variance_investigation_events')->insert([
                'stock_variance_investigation_id' => $investigationId,
                'event_type' => 'opened',
                'user_id' => $row->posted_by,
                'from_status' => null,
                'to_status' => 'open',
                'notes' => 'Backfilled from an unresolved posted stock variance.',
                'metadata' => json_encode(['source' => 'migration_backfill']),
                'created_at' => $openedAt,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_variance_investigation_events');
        Schema::dropIfExists('stock_variance_investigations');
    }
};
