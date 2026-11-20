<?php

namespace App\Actions\Projects;

use App\Actions\Inventory\AdjustStockAction;
use App\Models\InvoiceMilestone;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class ConvertQuoteToProjectAction
{
    public function __construct(
        private readonly AdjustStockAction $adjustStock,
    ) {}

    public function execute(Quote $quote, ?User $user = null): Project
    {
        if ($quote->project()->exists()) {
            return $quote->project->load(['tasks', 'materials', 'milestones']);
        }

        return DB::transaction(function () use ($quote, $user): Project {
            $quote->loadMissing(['items', 'lead', 'client']);

            $project = Project::create([
                'code' => $this->generateProjectCode(),
                'quote_id' => $quote->id,
                'client_id' => $quote->client_id,
                'site_id' => $quote->site_id,
                'company_profile_id' => $quote->company_profile_id,
                'lead_technician_id' => $quote->lead?->technician_id,
                'title' => $quote->lead?->title ?? "Proyecto {$quote->quote_number} - {$quote->client?->business_name}",
                'status' => 'planificado',
                'progress_percent' => 0,
                'start_date' => now()->toDateString(),
            ]);

            $defaultWarehouse = Warehouse::where('is_active', true)->first();

            foreach ($quote->items as $idx => $item) {
                $project->tasks()->create([
                    'section_name' => $item->section_name,
                    'title' => "Ejecutar / Instalar: {$item->description} ({$item->quantity} {$item->unit->value})",
                    'assigned_to' => $quote->lead?->technician_id,
                    'is_completed' => false,
                    'sort_order' => $idx,
                ]);

                $project->materials()->create([
                    'catalog_item_id' => $item->catalog_item_id,
                    'description' => $item->description,
                    'unit' => $item->unit->value,
                    'planned_qty' => $item->quantity,
                    'used_qty' => 0,
                    'unit_cost' => $item->unit_cost,
                ]);

                if ($defaultWarehouse && $item->catalog_item_id) {
                    $this->adjustStock->execute(
                        warehouseId: $defaultWarehouse->id,
                        catalogItemId: $item->catalog_item_id,
                        type: 'reserva',
                        quantity: (float) $item->quantity,
                        user: $user,
                        referenceType: 'Project',
                        referenceId: $project->id,
                        notes: "Reserva automática para {$project->code}",
                    );
                }
            }

            // Hitos de cobranza internos (50% Adelanto / 50% Contra entrega)
            $total = (float) $quote->total;
            $half = round($total * 0.5, 2);
            $remainder = round($total - $half, 2);

            InvoiceMilestone::create([
                'quote_id' => $quote->id,
                'project_id' => $project->id,
                'client_id' => $quote->client_id,
                'title' => 'Adelanto Inicial (50%)',
                'percentage' => 50.00,
                'amount' => $half,
                'paid_amount' => 0,
                'currency' => $quote->currency,
                'due_date' => now()->addDays(5)->toDateString(),
                'status' => 'pendiente',
            ]);

            InvoiceMilestone::create([
                'quote_id' => $quote->id,
                'project_id' => $project->id,
                'client_id' => $quote->client_id,
                'title' => 'Saldo Contra Entrega y Conformidad (50%)',
                'percentage' => 50.00,
                'amount' => $remainder,
                'paid_amount' => 0,
                'currency' => $quote->currency,
                'due_date' => now()->addDays(20)->toDateString(),
                'status' => 'pendiente',
            ]);

            AuditService::log('project.created_from_quote', $project, $user, [
                'quote_id' => $quote->id,
                'code' => $project->code,
            ]);

            return $project->load(['tasks', 'materials', 'milestones']);
        });
    }

    private function generateProjectCode(): string
    {
        $year = now()->format('Y');
        $prefix = "PRY-{$year}-";

        $lastCode = Project::withTrashed()
            ->where('code', 'like', "{$prefix}%")
            ->orderByDesc('code')
            ->value('code');

        $next = 1;
        if ($lastCode !== null) {
            $parts = explode('-', $lastCode);
            $next = ((int) end($parts)) + 1;
        }

        return sprintf('%s%04d', $prefix, $next);
    }
}
