<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->string('decision', 20);
            $table->string('signer_name', 150);
            $table->string('signer_document', 30)->nullable();
            $table->string('signer_email', 150)->nullable();
            $table->longText('signature_data')->nullable();
            $table->text('comment')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('accepted_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_acceptances');
    }
};
