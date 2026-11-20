<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Crm\MoveLeadStageAction;
use App\Enums\LeadStage;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::with([
            'client:id,business_name,document_number',
            'site:id,site_name,district_city',
            'assignedUser:id,name',
            'technician:id,name',
        ])->withCount('activities');

        if ($stage = $request->query('stage')) {
            $query->where('stage', $stage);
        }

        if ($serviceLine = $request->query('service_line')) {
            $query->where('service_line', $serviceLine);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($cq) => $cq->where('business_name', 'like', "%{$search}%"));
            });
        }

        $perPage = min((int) $request->query('per_page', 50), 100);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'site_id' => ['nullable', 'exists:client_sites,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:200'],
            'service_line' => ['required', Rule::in(['cctv', 'cableado_estructurado', 'redes_wifi', 'mantenimiento', 'integral'])],
            'stage' => ['sometimes', Rule::in(LeadStage::values())],
            'source' => ['sometimes', Rule::in(['web', 'referido', 'llamada', 'licitacion', 'cliente_recurrente'])],
            'estimated_amount' => ['numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['code'] = $this->generateLeadCode();
        $validated['assigned_user_id'] = $validated['assigned_user_id'] ?? $request->user()->id;
        $validated['stage'] = $validated['stage'] ?? LeadStage::Nuevo->value;
        $validated['source'] = $validated['source'] ?? 'web';

        $lead = Lead::create($validated);

        $lead->activities()->create([
            'user_id' => $request->user()->id,
            'activity_type' => 'nota',
            'subject' => 'Oportunidad creada',
            'description' => $validated['notes'] ?? 'Registro inicial en pipeline comercial.',
            'completed_at' => now(),
        ]);

        return response()->json([
            'data' => $lead->load(['client', 'site', 'assignedUser:id,name', 'technician:id,name', 'activities.user:id,name']),
        ], 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load([
            'client.contacts',
            'client.sites',
            'site',
            'assignedUser:id,name',
            'technician:id,name',
            'activities.user:id,name',
            'quotes' => fn ($q) => $q->latest('id'),
        ]);

        return response()->json(['data' => $lead]);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['sometimes', 'exists:clients,id'],
            'site_id' => ['nullable', 'exists:client_sites,id'],
            'assigned_user_id' => ['sometimes', 'exists:users,id'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:200'],
            'service_line' => ['sometimes', Rule::in(['cctv', 'cableado_estructurado', 'redes_wifi', 'mantenimiento', 'integral'])],
            'source' => ['sometimes', Rule::in(['web', 'referido', 'llamada', 'licitacion', 'cliente_recurrente'])],
            'estimated_amount' => ['numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead->update($validated);

        return response()->json([
            'data' => $lead->fresh(['client', 'site', 'assignedUser:id,name', 'technician:id,name', 'activities.user:id,name']),
        ]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(['message' => 'Oportunidad eliminada correctamente.']);
    }

    public function moveStage(Request $request, Lead $lead, MoveLeadStageAction $action): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', Rule::in(LeadStage::values())],
            'lost_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $updated = $action->execute(
            lead: $lead,
            newStage: LeadStage::from($validated['stage']),
            user: $request->user(),
            lostReason: $validated['lost_reason'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        return response()->json(['data' => $updated]);
    }

    public function storeActivity(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'activity_type' => ['required', Rule::in(['llamada', 'correo', 'reunion', 'visita_tecnica', 'nota'])],
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
        ]);

        $activity = $lead->activities()->create([
            ...$validated,
            'user_id' => $request->user()->id,
            'completed_at' => $validated['completed_at'] ?? now(),
        ]);

        return response()->json(['data' => $activity->load('user:id,name')], 201);
    }

    private function generateLeadCode(): string
    {
        $year = now()->format('Y');
        $prefix = "OPR-{$year}-";

        $lastCode = Lead::withTrashed()
            ->where('code', 'like', "{$prefix}%")
            ->orderByDesc('code')
            ->value('code');

        $nextSequence = 1;
        if ($lastCode !== null) {
            $parts = explode('-', $lastCode);
            $nextSequence = ((int) end($parts)) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextSequence);
    }
}
