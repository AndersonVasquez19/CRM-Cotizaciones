<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->unique()->after('quote_number');
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('approval_requested_at')->nullable()->after('approved_at');
            $table->text('approval_notes')->nullable()->after('approval_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'public_token',
                'approved_by',
                'approved_at',
                'approval_requested_at',
                'approval_notes',
            ]);
        });
    }
};
