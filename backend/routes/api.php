<?php

use App\Http\Controllers\Api\Public\ClientQuotePortalController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogCategoryController;
use App\Http\Controllers\Api\V1\CatalogItemController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CompanyProfileController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\MaintenanceContractController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\QuoteController;
use App\Http\Controllers\Api\V1\QuoteTemplateController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\SiteSurveyController;
use App\Http\Controllers\Api\V1\SlaTicketController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

// Rutas públicas del Portal Cliente (Magic Link)
Route::prefix('public')->group(function () {
    Route::get('quotes/{token}', [ClientQuotePortalController::class, 'show']);
    Route::get('quotes/{token}/pdf', [ClientQuotePortalController::class, 'pdf']);
    Route::post('quotes/{token}/decision', [ClientQuotePortalController::class, 'decision']);
});

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('dashboard/metrics', [DashboardController::class, 'metrics']);

        Route::apiResource('users', UserController::class)->except(['show']);
        Route::apiResource('company-profiles', CompanyProfileController::class)->except(['show']);
        Route::get('settings', [SettingController::class, 'index']);
        Route::put('settings', [SettingController::class, 'update']);
        Route::get('activity-logs', [ActivityLogController::class, 'index']);

        Route::apiResource('clients', ClientController::class);
        Route::post('clients/{client}/contacts', [ClientController::class, 'storeContact']);
        Route::put('clients/{client}/contacts/{contact}', [ClientController::class, 'updateContact']);
        Route::delete('clients/{client}/contacts/{contact}', [ClientController::class, 'destroyContact']);
        Route::post('clients/{client}/sites', [ClientController::class, 'storeSite']);
        Route::put('clients/{client}/sites/{site}', [ClientController::class, 'updateSite']);
        Route::delete('clients/{client}/sites/{site}', [ClientController::class, 'destroySite']);

        Route::apiResource('leads', LeadController::class);
        Route::patch('leads/{lead}/stage', [LeadController::class, 'moveStage']);
        Route::post('leads/{lead}/activities', [LeadController::class, 'storeActivity']);
        Route::get('leads/{lead}/surveys', [SiteSurveyController::class, 'index']);
        Route::post('leads/{lead}/surveys', [SiteSurveyController::class, 'store']);

        Route::apiResource('catalog-categories', CatalogCategoryController::class);
        Route::apiResource('catalog-items', CatalogItemController::class);
        Route::apiResource('quote-templates', QuoteTemplateController::class)->except(['update']);

        Route::apiResource('quotes', QuoteController::class);
        Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate']);
        Route::patch('quotes/{quote}/status', [QuoteController::class, 'changeStatus']);
        Route::post('quotes/{quote}/approve-margin', [QuoteController::class, 'approveMargin']);
        Route::post('quotes/{quote}/send-email', [QuoteController::class, 'sendEmail']);
        Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf']);

        // Fase 3: Proyectos, Almacén y Compras
        Route::get('projects', [ProjectController::class, 'index']);
        Route::get('projects/{project}', [ProjectController::class, 'show']);
        Route::put('projects/{project}', [ProjectController::class, 'update']);
        Route::patch('projects/{project}/tasks/{task}/toggle', [ProjectController::class, 'toggleTask']);
        Route::patch('projects/{project}/materials/{material}', [ProjectController::class, 'updateMaterial']);
        Route::get('projects/{project}/conformity-pdf', [ProjectController::class, 'conformityPdf']);

        Route::get('inventory/warehouses', [InventoryController::class, 'warehouses']);
        Route::get('inventory/stocks', [InventoryController::class, 'stocks']);
        Route::get('inventory/movements', [InventoryController::class, 'movements']);
        Route::post('inventory/adjust', [InventoryController::class, 'adjust']);

        Route::apiResource('suppliers', SupplierController::class)->only(['index', 'store', 'update']);
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);

        // Fase 4: Contratos SLA y Finanzas Internas
        Route::apiResource('maintenance-contracts', MaintenanceContractController::class)->only(['index', 'store', 'update']);
        Route::apiResource('sla-tickets', SlaTicketController::class)->only(['index', 'store', 'update']);
        Route::get('finance/milestones', [FinanceController::class, 'milestones']);
        Route::post('finance/milestones/{milestone}/payments', [FinanceController::class, 'recordPayment']);

        // Fase 5: Reportes BI
        Route::get('reports/summary', [ReportController::class, 'summary']);
        Route::get('reports/export', [ReportController::class, 'export']);
    });
});
