<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Client::with(['assignedUser:id,name'])
            ->withCount(['contacts', 'sites', 'quotes', 'leads']);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($query->latest('id')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', Rule::in(['RUC', 'DNI', 'CE', 'PASAPORTE'])],
            'document_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('clients')->where(fn ($q) => $q->where('document_type', $request->input('document_type'))),
            ],
            'business_name' => ['required', 'string', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'sector' => ['nullable', 'string', 'max:100'],
            'fiscal_address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:150'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        if (empty($validated['assigned_user_id'])) {
            $validated['assigned_user_id'] = $request->user()?->id;
        }

        $client = Client::create($validated);

        return response()->json([
            'data' => $client->load(['assignedUser:id,name', 'contacts', 'sites']),
        ], 201);
    }

    public function show(Client $client): JsonResponse
    {
        $client->load([
            'assignedUser:id,name',
            'contacts',
            'sites',
            'quotes' => fn ($q) => $q->with('creator:id,name')->latest('id'),
            'leads' => fn ($q) => $q->latest('id'),
        ]);

        return response()->json(['data' => $client]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $docType = $request->input('document_type', $client->document_type);

        $validated = $request->validate([
            'document_type' => ['sometimes', Rule::in(['RUC', 'DNI', 'CE', 'PASAPORTE'])],
            'document_number' => [
                'sometimes',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('clients')
                    ->ignore($client->id)
                    ->where(fn ($q) => $q->where('document_type', $docType)),
            ],
            'business_name' => ['sometimes', 'string', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'sector' => ['nullable', 'string', 'max:100'],
            'fiscal_address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:150'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        $client->update($validated);

        return response()->json([
            'data' => $client->fresh(['assignedUser:id,name', 'contacts', 'sites']),
        ]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(['message' => 'Cliente eliminado correctamente.']);
    }

    public function storeContact(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:25'],
            'is_primary' => ['boolean'],
        ]);

        if (! empty($validated['is_primary'])) {
            $client->contacts()->update(['is_primary' => false]);
        }

        $contact = $client->contacts()->create($validated);

        return response()->json(['data' => $contact], 201);
    }

    public function updateContact(Request $request, Client $client, ClientContact $contact): JsonResponse
    {
        abort_if($contact->client_id !== $client->id, 404);

        $validated = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:25'],
            'is_primary' => ['boolean'],
        ]);

        if (! empty($validated['is_primary'])) {
            $client->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $contact->update($validated);

        return response()->json(['data' => $contact]);
    }

    public function destroyContact(Client $client, ClientContact $contact): JsonResponse
    {
        abort_if($contact->client_id !== $client->id, 404);
        $contact->delete();

        return response()->json(['message' => 'Contacto eliminado.']);
    }

    public function storeSite(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'district_city' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $site = $client->sites()->create($validated);

        return response()->json(['data' => $site], 201);
    }

    public function updateSite(Request $request, Client $client, ClientSite $site): JsonResponse
    {
        abort_if($site->client_id !== $client->id, 404);

        $validated = $request->validate([
            'site_name' => ['sometimes', 'string', 'max:150'],
            'address' => ['sometimes', 'string', 'max:255'],
            'district_city' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $site->update($validated);

        return response()->json(['data' => $site]);
    }

    public function destroySite(Client $client, ClientSite $site): JsonResponse
    {
        abort_if($site->client_id !== $client->id, 404);
        $site->delete();

        return response()->json(['message' => 'Sede eliminada.']);
    }
}
