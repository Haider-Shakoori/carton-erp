<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->unsignedInteger('revision_sequence')->default(1)->after('version');
            $table->date('effective_from')->nullable()->after('status');
            $table->date('effective_to')->nullable()->after('effective_from');
            $table->foreignId('supersedes_bom_id')->nullable()->after('effective_to')
                ->constrained('boms')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('updated_by')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('locked_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by');

            $table->index(['product_id', 'revision_sequence']);
            $table->index(['product_id', 'effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'revision_sequence']);
            $table->dropIndex(['product_id', 'effective_from', 'effective_to']);
            $table->dropConstrainedForeignId('supersedes_bom_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn([
                'revision_sequence',
                'effective_from',
                'effective_to',
                'approved_at',
                'locked_at',
            ]);
        });
    }
};
