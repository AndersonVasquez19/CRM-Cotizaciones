<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage', User::class), 403, 'Solo el administrador puede modificar parámetros.');

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.min_margin_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'settings.max_discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'settings.company_name' => ['sometimes', 'string', 'max:200'],
            'settings.company_ruc' => ['sometimes', 'string', 'max:20'],
            'settings.company_address' => ['sometimes', 'string', 'max:255'],
            'settings.company_email' => ['sometimes', 'email', 'max:150'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::setValue($key, $value, str_starts_with($key, 'company_') ? 'company' : 'commercial');
        }

        $first = Setting::first();
        if ($first) {
            AuditService::log('settings.updated', $first, $request->user(), $validated['settings']);
        }

        return response()->json(['data' => Setting::all()->pluck('value', 'key')]);
    }
}
