<?php

use App\Enums\LeadStage;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Mail\MarginApprovalRequestedMail;
use App\Mail\QuoteSentMail;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('enforces RBAC: hides internal costs from tecnico and restricts user management to admin', function () {
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@rbactest.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $tecnico = User::create([
        'name' => 'Tecnico',
        'email' => 'tecnico@rbactest.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Tecnico,
        'is_active' => true,
    ]);

    $client = Client::create([
        'document_type' => 'RUC',
        'document_number' => '20600001111',
        'business_name' => 'Cliente RBAC S.A.C.',
        'assigned_user_id' => $admin->id,
    ]);

    $this->actingAs($admin, 'sanctum');
    $quoteRes = $this->postJson('/api/v1/quotes', [
        'client_id' => $client->id,
        'items' => [
            [
                'section_name' => '1. Equipos',
                'description' => 'Cámara IP',
                'unit' => 'UND',
                'quantity' => 1,
                'unit_cost' => 150,
                'unit_price' => 300,
            ],
        ],
    ])->assertCreated();

    $quoteId = $quoteRes->json('data.id');

    // Técnico cannot see unit_cost or manage users
    $this->actingAs($tecnico, 'sanctum');
    $this->getJson('/api/v1/users')->assertForbidden();

    $showRes = $this->getJson("/api/v1/quotes/{$quoteId}")->assertOk();
    expect($showRes->json('data.items.0'))->not->toHaveKey('unit_cost');

    // Admin can manage users
    $this->actingAs($admin, 'sanctum');
    $this->getJson('/api/v1/users')->assertOk();
});

it('requires supervisor approval when quote margin is below threshold and sends email alerts', function () {
    Mail::fake();

    Setting::setValue('min_margin_percent', '20', 'commercial');
    Setting::setValue('max_discount_percent', '10', 'commercial');

    $supervisor = User::create([
        'name' => 'Supervisor',
        'email' => 'sup@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Supervisor,
        'is_active' => true,
    ]);

    $comercial = User::create([
        'name' => 'Comercial',
        'email' => 'com@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Comercial,
        'is_active' => true,
    ]);

    $client = Client::create([
        'document_type' => 'RUC',
        'document_number' => '20600002222',
        'business_name' => 'Cliente Margen S.A.C.',
        'email' => 'cliente@margen.pe',
        'assigned_user_id' => $comercial->id,
    ]);

    $this->actingAs($comercial, 'sanctum');

    // Margin = (110 - 100) / 110 = 9.09% < 20%
    $quoteRes = $this->postJson('/api/v1/quotes', [
        'client_id' => $client->id,
        'items' => [
            [
                'section_name' => '1. Equipos',
                'description' => 'Switch PoE',
                'unit' => 'UND',
                'quantity' => 1,
                'unit_cost' => 100,
                'unit_price' => 110,
            ],
        ],
    ])->assertCreated();

    $quoteId = $quoteRes->json('data.id');

    // Comercial tries to mark as enviada -> blocked into pendiente_aprobacion
    $this->patchJson("/api/v1/quotes/{$quoteId}/status", [
        'status' => QuoteStatus::Enviada->value,
    ])->assertOk()
        ->assertJsonPath('data.status', QuoteStatus::PendienteAprobacion->value);

    Mail::assertSent(MarginApprovalRequestedMail::class);

    // Comercial cannot approve margin
    $this->postJson("/api/v1/quotes/{$quoteId}/approve-margin")->assertForbidden();

    // Supervisor approves margin -> transitions to enviada
    $this->actingAs($supervisor, 'sanctum');
    $this->postJson("/api/v1/quotes/{$quoteId}/approve-margin", [
        'notes' => 'Aprobado por volumen estratégico.',
    ])->assertOk()
        ->assertJsonPath('data.status', QuoteStatus::Enviada->value)
        ->assertJsonPath('data.approved_by', $supervisor->id);

    // Send email with PDF to client
    $this->postJson("/api/v1/quotes/{$quoteId}/send-email", [
        'email' => 'cliente@margen.pe',
    ])->assertOk();

    Mail::assertSent(QuoteSentMail::class, fn ($mail) => $mail->hasTo('cliente@margen.pe'));
});

it('supports BOM templates, site surveys, and public client portal digital signature approval', function () {
    $user = User::create([
        'name' => 'Comercial',
        'email' => 'com2@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Comercial,
        'is_active' => true,
    ]);

    $client = Client::create([
        'document_type' => 'RUC',
        'document_number' => '20600003333',
        'business_name' => 'Cliente Portal S.A.C.',
        'assigned_user_id' => $user->id,
    ]);

    $lead = Lead::create([
        'code' => 'OPR-2026-0777',
        'client_id' => $client->id,
        'assigned_user_id' => $user->id,
        'title' => 'Portal Test CCTV',
        'service_line' => 'cctv',
        'stage' => LeadStage::Cotizado,
        'estimated_amount' => 2500,
    ]);

    $this->actingAs($user, 'sanctum');

    // BOM Template
    $this->postJson('/api/v1/quote-templates', [
        'name' => 'Kit Básico 4 Cámaras',
        'service_line' => 'cctv',
        'items' => [
            [
                'section_name' => '1. Equipamiento',
                'description' => 'Cámara IP 4MP',
                'unit' => 'UND',
                'quantity' => 4,
                'unit_cost' => 200,
                'unit_price' => 350,
            ],
        ],
    ])->assertCreated();

    $this->getJson('/api/v1/quote-templates')->assertOk()->assertJsonCount(1, 'data');

    // Site Survey
    $this->postJson("/api/v1/leads/{$lead->id}/surveys", [
        'cable_meters' => 145.5,
        'conduit_type' => 'Tubería EMT 3/4"',
        'max_height_meters' => 4.2,
        'power_requirements' => 'Toma estabilizada en rack',
        'observations' => 'Techo de concreto.',
    ])->assertCreated();

    $this->getJson("/api/v1/leads/{$lead->id}/surveys")->assertOk()->assertJsonCount(1, 'data');

    // Quote + Public Portal
    $quoteRes = $this->postJson('/api/v1/quotes', [
        'client_id' => $client->id,
        'lead_id' => $lead->id,
        'items' => [
            [
                'section_name' => '1. Equipamiento',
                'description' => 'Cámara IP 4MP',
                'unit' => 'UND',
                'quantity' => 4,
                'unit_cost' => 200,
                'unit_price' => 350,
            ],
        ],
    ])->assertCreated();

    $token = $quoteRes->json('data.public_token');
    expect($token)->not->toBeEmpty();

    // Public GET without auth
    auth()->forgetGuards();
    $this->getJson("/api/public/quotes/{$token}")
        ->assertOk()
        ->assertJsonPath('data.client.business_name', 'Cliente Portal S.A.C.');

    // Public POST digital signature approval
    $this->postJson("/api/public/quotes/{$token}/decision", [
        'decision' => 'aprobada',
        'signer_name' => 'Gerente General',
        'signer_document' => '44556677',
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        'comment' => 'Conforme, proceder con instalación.',
    ])->assertCreated()
        ->assertJsonPath('quote_status', QuoteStatus::Aprobada->value);

    expect($lead->fresh()->stage)->toBe(LeadStage::Ganado);
});
