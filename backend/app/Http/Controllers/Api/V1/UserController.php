<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage', User::class), 403, 'Acceso denegado.');

        $users = User::orderBy('name')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role->value,
            'role_label' => $u->role->label(),
            'phone' => $u->phone,
            'is_active' => $u->is_active,
            'created_at' => $u->created_at,
        ]);

        return response()->json(['data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage', User::class), 403, 'Acceso denegado.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(UserRole::values())],
            'phone' => ['nullable', 'string', 'max:25'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        AuditService::log('user.created', $user, $request->user(), ['role' => $user->role->value]);

        return response()->json(['data' => $user], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->can('manage', User::class), 403, 'Acceso denegado.');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['sometimes', Rule::in(UserRole::values())],
            'phone' => ['nullable', 'string', 'max:25'],
            'is_active' => ['boolean'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        AuditService::log('user.updated', $user, $request->user(), ['role' => $user->role->value]);

        return response()->json(['data' => $user]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->can('manage', User::class), 403, 'Acceso denegado.');
        abort_if($user->id === $request->user()->id, 422, 'No puedes eliminar tu propio usuario.');

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }
}
