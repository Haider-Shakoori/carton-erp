<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->unsignedBigInteger('received_by')->nullable()->after('approval_notes');
            $table->timestamp('received_at')->nullable()->after('received_by');
            $table->string('invoice_match_status', 30)->default('not_received')->after('received_at');
            $table->unsignedBigInteger('matched_by')->nullable()->after('invoice_match_status');
            $table->timestamp('matched_at')->nullable()->after('matched_by');
            $table->index(['status', 'approved_at']);
            $table->index('invoice_match_status');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['status', 'approved_at']);
            $table->dropIndex(['invoice_match_status']);
            $table->dropColumn(['approved_by','approved_at','approval_notes','received_by','received_at','invoice_match_status','matched_by','matched_at']);
        });
    }
};
