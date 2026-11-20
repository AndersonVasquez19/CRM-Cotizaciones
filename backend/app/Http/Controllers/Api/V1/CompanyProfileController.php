<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyProfileController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => CompanyProfile::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage', User::class), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'ruc' => ['required', 'string', 'max:20', 'unique:company_profiles,ruc'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'bank_accounts' => ['nullable', 'string'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            CompanyProfile::query()->update(['is_default' => false]);
        }

        $profile = CompanyProfile::create($validated);

        return response()->json(['data' => $profile], 201);
    }

    public function update(Request $request, CompanyProfile $companyProfile): JsonResponse
    {
        abort_unless($request->user()->can('manage', User::class), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'ruc' => ['sometimes', 'string', 'max:20', Rule::unique('company_profiles', 'ruc')->ignore($companyProfile->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'bank_accounts' => ['nullable', 'string'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            CompanyProfile::where('id', '!=', $companyProfile->id)->update(['is_default' => false]);
        }

        $companyProfile->update($validated);

        return response()->json(['data' => $companyProfile]);
    }

    public function destroy(Request $request, CompanyProfile $companyProfile): JsonResponse
    {
        abort_unless($request->user()->can('manage', User::class), 403);
        $companyProfile->delete();

        return response()->json(['message' => 'Empresa eliminada.']);
    }
}
