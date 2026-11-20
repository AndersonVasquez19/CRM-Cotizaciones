<?php

use App\Enums\LeadStage;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a client with contacts and sites, creates a lead, moves its stage and logs technical visit', function () {
    $user = User::create([
        'name' => 'Comercial',
        'email' => 'comercial@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Comercial,
        'is_active' => true,
    ]);

    $this->actingAs($user, 'sanctum');

    $clientRes = $this->postJson('/api/v1/clients', [
        'document_type' => 'RUC',
        'document_number' => '20555666777',
        'business_name' => 'Empresa Tecnológica SAC',
        'sector' => 'Retail',
    ])->assertCreated();

    $clientId = $clientRes->json('data.id');

    $this->postJson("/api/v1/clients/{$clientId}/contacts", [
        'full_name' => 'Ana Gómez',
        'position' => 'Gerente TI',
        'email' => 'ana@empresa.pe',
        'is_primary' => true,
    ])->assertCreated();

    $siteRes = $this->postJson("/api/v1/clients/{$clientId}/sites", [
        'site_name' => 'Sede Miraflores',
        'address' => 'Av. Larco 450',
        'district_city' => 'Lima',
    ])->assertCreated();

    $siteId = $siteRes->json('data.id');

    $leadRes = $this->postJson('/api/v1/leads', [
        'client_id' => $clientId,
        'site_id' => $siteId,
        'title' => 'Instalación 10 Cámaras IP',
        'service_line' => 'cctv',
        'estimated_amount' => 8500,
    ])->assertCreated();

    $leadId = $leadRes->json('data.id');
    expect($leadRes->json('data.code'))->toStartWith('OPR-');

    $this->patchJson("/api/v1/leads/{$leadId}/stage", [
        'stage' => LeadStage::VisitaTecnica->value,
        'notes' => 'Coordinada visita técnica.',
    ])->assertOk()
        ->assertJsonPath('data.stage', LeadStage::VisitaTecnica->value);

    $this->postJson("/api/v1/leads/{$leadId}/activities", [
        'activity_type' => 'visita_tecnica',
        'subject' => 'Levantamiento de metraje',
        'description' => '180 metros de cable Cat6A.',
    ])->assertCreated();

    $this->getJson("/api/v1/leads/{$leadId}")
        ->assertOk()
        ->assertJsonCount(3, 'data.activities');
});
