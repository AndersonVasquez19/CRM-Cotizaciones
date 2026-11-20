<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceContractController extends Controller
{
    public function index(): JsonResponse
    {
        $contracts = MaintenanceContract::with(['client:id,business_name', 'site:id,site_name'])
            ->withCount('tickets')
            ->latest('id')
            ->get();

        return response()->json(['data' => $contracts]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'site_id' => ['nullable', 'exists:client_sites,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:200'],
            'frequency' => ['required', Rule::in(['mensual', 'trimestral', 'semestral', 'anual'])],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', Rule::in(['PEN', 'USD'])],
            'sla_hours' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'next_visit_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $year = now()->format('Y');
        $prefix = "CTR-{$year}-";
        $last = MaintenanceContract::where('contract_number', 'like', "{$prefix}%")->orderByDesc('contract_number')->value('contract_number');
        $seq = $last ? ((int) explode('-', $last)[2]) + 1 : 1;

        $validated['contract_number'] = sprintf('%s%04d', $prefix, $seq);
        $validated['next_visit_date'] = $validated['next_visit_date'] ?? $validated['start_date'];

        $contract = MaintenanceContract::create($validated);

        return response()->json(['data' => $contract->load(['client:id,business_name', 'site:id,site_name'])], 201);
    }

    public function update(Request $request, MaintenanceContract $maintenanceContract): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['activo', 'suspendido', 'vencido', 'cancelado'])],
            'next_visit_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $maintenanceContract->update($validated);

        return response()->json(['data' => $maintenanceContract]);
    }
}
