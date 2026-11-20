<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number', 30);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('client_contacts')->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('client_sites')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('borrador')->index();
            $table->string('currency', 3)->default('PEN');
            $table->decimal('exchange_rate', 8, 4)->default(1.0000);
            $table->date('issue_date');
            $table->date('valid_until');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('payment_terms', 255)->nullable();
            $table->string('delivery_time', 255)->nullable();
            $table->string('warranty_terms', 255)->nullable();
            $table->text('technical_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['quote_number', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
