<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('authenticates a valid user and returns bearer token and user profile', function () {
    $user = User::create([
        'name' => 'Admin Demo',
        'email' => 'admin@techcrm.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@techcrm.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'role', 'role_label', 'is_active'],
        ])
        ->assertJsonPath('user.email', $user->email);
});

it('rejects invalid credentials', function () {
    User::create([
        'name' => 'Admin Demo',
        'email' => 'admin@techcrm.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@techcrm.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable();
});

it('returns authenticated user on me and revokes token on logout', function () {
    $user = User::create([
        'name' => 'Comercial Demo',
        'email' => 'comercial@techcrm.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Comercial,
        'is_active' => true,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('user.email', 'comercial@techcrm.com');

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();
});
