<?php

use App\Enums\CatalogItemType;
use App\Enums\MeasurementUnit;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\CompanyProfile;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Hash;

it('auto-creates project, reserves stock, generates milestones, records payments, and exports conformity pdf', function () {
    $admin = User::create([
        'name' => 'Admin Phase3',
        'email' => 'admin3@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $company = CompanyProfile::create([
        'name' => 'Empresa Emisora S.A.C.',
        'ruc' => '20601119999',
        'is_default' => true,
    ]);

    $warehouse = Warehouse::create(['name' => 'Almacén Test', 'is_active' => true]);

    $category = CatalogCategory::create(['name' => 'CCTV', 'slug' => 'cctv-p3']);
    $item = CatalogItem::create([
        'category_id' => $category->id,
        'sku' => 'CAM-P3-01',
        'name' => 'Cámara IP 4MP',
        'item_type' => CatalogItemType::Equipo,
        'unit' => MeasurementUnit::UND,
        'unit_cost' => 200,
        'sale_price' => 400,
    ]);

    WarehouseStock::create([
        'warehouse_id' => $warehouse->id,
        'catalog_item_id' => $item->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
    ]);

    $client = Client::create([
        'document_type' => 'RUC',
        'document_number' => '20608887776',
        'business_name' => 'Cliente Proyecto S.A.C.',
        'assigned_user_id' => $admin->id,
    ]);

    $this->actingAs($admin, 'sanctum');

    // Create & approve quote with company_profile_id
    $quoteRes = $this->postJson('/api/v1/quotes', [
        'client_id' => $client->id,
        'company_profile_id' => $company->id,
        'items' => [
            [
                'catalog_item_id' => $item->id,
                'section_name' => '1. Equipos',
                'description' => 'Cámara IP 4MP',
                'unit' => 'UND',
                'quantity' => 3,
                'unit_cost' => 200,
                'unit_price' => 400,
            ],
        ],
    ])->assertCreated();

    $quoteId = $quoteRes->json('data.id');

    $this->patchJson("/api/v1/quotes/{$quoteId}/status", [
        'status' => QuoteStatus::Aprobada->value,
    ])->assertOk();

    // Check Project created
    $projectsRes = $this->getJson('/api/v1/projects')->assertOk();
    expect($projectsRes->json('data'))->toHaveCount(1);
    $projectId = $projectsRes->json('data.0.id');
    expect($projectsRes->json('data.0.code'))->toStartWith('PRY-');

    // Check stock reserved
    $stock = WarehouseStock::where('warehouse_id', $warehouse->id)->where('catalog_item_id', $item->id)->first();
    expect((float) $stock->reserved_quantity)->toBe(3.0);

    // Toggle task -> 100% progress
    $detail = $this->getJson("/api/v1/projects/{$projectId}")->assertOk();
    $taskId = $detail->json('data.tasks.0.id');
    $this->patchJson("/api/v1/projects/{$projectId}/tasks/{$taskId}/toggle")
        ->assertOk()
        ->assertJsonPath('data.progress_percent', 100)
        ->assertJsonPath('data.status', 'completado');

    // Conformity PDF
    $pdfRes = $this->get("/api/v1/projects/{$projectId}/conformity-pdf");
    $pdfRes->assertOk();
    expect($pdfRes->headers->get('content-type'))->toContain('application/pdf');

    // Finance Milestones & Payments
    $finRes = $this->getJson('/api/v1/finance/milestones')->assertOk();
    expect($finRes->json('data'))->toHaveCount(2);
    $milestoneId = $finRes->json('data.0.id');
    $milestoneAmount = (float) $finRes->json('data.0.amount');

    $this->postJson("/api/v1/finance/milestones/{$milestoneId}/payments", [
        'amount' => $milestoneAmount,
        'payment_method' => 'transferencia',
        'reference_number' => 'OP-12345',
        'paid_at' => now()->toDateString(),
    ])->assertCreated()
        ->assertJsonPath('data.status', 'pagado');
});

it('receives purchase orders into stock, manages SLA contracts/tickets, and generates BI reports', function () {
    $admin = User::create([
        'name' => 'Admin Phase4',
        'email' => 'admin4@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create(['name' => 'Almacén Principal', 'is_active' => true]);
    $supplier = Supplier::create(['ruc' => '20551122334', 'business_name' => 'Proveedor Demo SAC']);
    $category = CatalogCategory::create(['name' => 'Redes', 'slug' => 'redes-p4']);
    $item = CatalogItem::create([
        'category_id' => $category->id,
        'sku' => 'SW-P4-01',
        'name' => 'Switch PoE 16P',
        'item_type' => CatalogItemType::Equipo,
        'unit' => MeasurementUnit::UND,
        'unit_cost' => 500,
        'sale_price' => 900,
    ]);

    $client = Client::create([
        'document_type' => 'RUC',
        'document_number' => '20604445556',
        'business_name' => 'Cliente SLA S.A.C.',
        'assigned_user_id' => $admin->id,
    ]);

    $this->actingAs($admin, 'sanctum');

    // Purchase Order + Receive
    $poRes = $this->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'items' => [
            ['catalog_item_id' => $item->id, 'quantity' => 5, 'unit_cost' => 500],
        ],
    ])->assertCreated();

    $poId = $poRes->json('data.id');
    $this->postJson("/api/v1/purchase-orders/{$poId}/receive")
        ->assertOk()
        ->assertJsonPath('data.status', 'recibida');

    $stock = WarehouseStock::where('warehouse_id', $warehouse->id)->where('catalog_item_id', $item->id)->first();
    expect((float) $stock->quantity)->toBe(5.0);

    // Maintenance Contract & SLA Ticket
    $ctrRes = $this->postJson('/api/v1/maintenance-contracts', [
        'client_id' => $client->id,
        'title' => 'Póliza Anual Redes',
        'frequency' => 'mensual',
        'monthly_fee' => 600,
        'sla_hours' => 8,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ])->assertCreated();

    $this->postJson('/api/v1/sla-tickets', [
        'contract_id' => $ctrRes->json('data.id'),
        'client_id' => $client->id,
        'priority' => 'alta',
        'subject' => 'Caída de enlace Wi-Fi Piso 2',
    ])->assertCreated();

    // Reports BI
    $this->getJson('/api/v1/reports/summary')
        ->assertOk()
        ->assertJsonStructure(['period', 'kpis', 'by_service_line', 'by_seller']);
});
