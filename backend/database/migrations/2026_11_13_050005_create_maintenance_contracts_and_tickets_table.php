<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 30)->unique();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('client_sites')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('title', 200);
            $table->string('frequency', 20)->default('trimestral');
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->string('currency', 3)->default('PEN');
            $table->unsignedInteger('sla_hours')->default(24);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_visit_date')->nullable();
            $table->string('status', 20)->default('activo')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sla_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 30)->unique();
            $table->foreignId('contract_id')->nullable()->constrained('maintenance_contracts')->nullOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority', 20)->default('media')->index();
            $table->string('status', 20)->default('abierto')->index();
            $table->string('subject', 200);
            $table->text('description')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_tickets');
        Schema::dropIfExists('maintenance_contracts');
    }
};
