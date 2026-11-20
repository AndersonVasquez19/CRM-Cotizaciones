<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceContract;
use App\Models\SlaTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SlaTicketController extends Controller
{
    public function index(): JsonResponse
    {
        $tickets = SlaTicket::with([
            'client:id,business_name',
            'contract:id,contract_number,sla_hours',
            'assignee:id,name',
        ])->latest('id')->get();

        return response()->json(['data' => $tickets]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contract_id' => ['nullable', 'exists:maintenance_contracts,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', Rule::in(['baja', 'media', 'alta', 'critica'])],
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
        ]);

        $year = now()->format('Y');
        $prefix = "TKT-{$year}-";
        $last = SlaTicket::where('ticket_number', 'like', "{$prefix}%")->orderByDesc('ticket_number')->value('ticket_number');
        $seq = $last ? ((int) explode('-', $last)[2]) + 1 : 1;

        $slaHours = 24;
        if (! empty($validated['contract_id'])) {
            $contract = MaintenanceContract::find($validated['contract_id']);
            if ($contract) {
                $slaHours = $contract->sla_hours;
            }
        }

        $ticket = SlaTicket::create([
            ...$validated,
            'ticket_number' => sprintf('%s%04d', $prefix, $seq),
            'status' => 'abierto',
            'due_at' => now()->addHours($slaHours),
        ]);

        return response()->json(['data' => $ticket->load(['client:id,business_name', 'contract:id,contract_number', 'assignee:id,name'])], 201);
    }

    public function update(Request $request, SlaTicket $slaTicket): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['abierto', 'en_proceso', 'resuelto', 'cerrado'])],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        if (in_array($validated['status'] ?? null, ['resuelto', 'cerrado'], true) && $slaTicket->resolved_at === null) {
            $validated['resolved_at'] = now();
        }

        $slaTicket->update($validated);

        return response()->json(['data' => $slaTicket]);
    }
}
