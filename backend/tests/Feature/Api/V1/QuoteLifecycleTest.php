<?php

use App\Enums\CatalogItemType;
use App\Enums\LeadStage;
use App\Enums\MeasurementUnit;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('runs full quote lifecycle: create, duplicate v2, approve winning lead, and generate pdf', function () {
    $user = User::create([
        'name' => 'Ejecutivo Demo',
        'email' => 'exec@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Comercial,
        'is_active' => true,
    ]);

    $client = Client::create([
        'document_type' => 'RUC',
        'document_number' => '20601112223',
        'business_name' => 'Corporación Demo S.A.C.',
        'assigned_user_id' => $user->id,
    ]);

    $lead = Lead::create([
        'code' => 'OPR-2026-0099',
        'client_id' => $client->id,
        'assigned_user_id' => $user->id,
        'title' => 'Proyecto CCTV Sede Norte',
        'service_line' => 'cctv',
        'stage' => LeadStage::Cotizado,
        'estimated_amount' => 5000,
    ]);

    $category = CatalogCategory::create([
        'name' => 'CCTV',
        'slug' => 'cctv',
    ]);

    $item = CatalogItem::create([
        'category_id' => $category->id,
        'sku' => 'CAM-TEST-01',
        'name' => 'Cámara IP 4MP',
        'item_type' => CatalogItemType::Equipo,
        'unit' => MeasurementUnit::UND,
        'unit_cost' => 200,
        'sale_price' => 350,
    ]);

    $this->actingAs($user, 'sanctum');

    // 1. Create Quote v1
    $createRes = $this->postJson('/api/v1/quotes', [
        'client_id' => $client->id,
        'lead_id' => $lead->id,
        'currency' => 'PEN',
        'discount_amount' => 100,
        'items' => [
            [
                'catalog_item_id' => $item->id,
                'section_name' => '1. Equipamiento CCTV',
                'description' => 'Cámara IP 4MP',
                'unit' => 'UND',
                'quantity' => 4,
                'unit_cost' => 200,
                'unit_price' => 350,
                'discount_percent' => 0,
            ],
        ],
    ])->assertCreated();

    $quoteId = $createRes->json('data.id');
    expect((float) $createRes->json('data.subtotal'))->toBe(1400.0)
        ->and((float) $createRes->json('data.tax_amount'))->toBe(234.0) // (1400-100)*0.18
        ->and((float) $createRes->json('data.total'))->toBe(1534.0)
        ->and($createRes->json('data.version'))->toBe(1);

    // 2. Duplicate to v2
    $dupRes = $this->postJson("/api/v1/quotes/{$quoteId}/duplicate")
        ->assertCreated()
        ->assertJsonPath('data.version', 2)
        ->assertJsonPath('data.quote_number', $createRes->json('data.quote_number'))
        ->assertJsonPath('data.status', QuoteStatus::Borrador->value);

    $v2Id = $dupRes->json('data.id');

    // 3. Approve v2 -> automatically moves Lead to Ganado
    $this->patchJson("/api/v1/quotes/{$v2Id}/status", [
        'status' => QuoteStatus::Aprobada->value,
        'comment' => 'Aprobada por cliente',
    ])->assertOk()
        ->assertJsonPath('data.status', QuoteStatus::Aprobada->value);

    expect($lead->fresh()->stage)->toBe(LeadStage::Ganado);

    // 4. Generate PDF
    $pdfRes = $this->get("/api/v1/quotes/{$v2Id}/pdf");
    $pdfRes->assertOk();
    expect($pdfRes->headers->get('content-type'))->toContain('application/pdf');

    // 5. Dashboard metrics endpoint works
    $this->getJson('/api/v1/dashboard/metrics')
        ->assertOk()
        ->assertJsonStructure([
            'kpis' => ['monthly_quoted_total', 'monthly_approved_total', 'lead_conversion_rate', 'active_clients'],
            'pipeline_by_stage',
            'recent_quotes',
        ]);
});
