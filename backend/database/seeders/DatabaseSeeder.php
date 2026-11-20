<?php

namespace Database\Seeders;

use App\Actions\Quotes\ChangeQuoteStatusAction;
use App\Actions\Quotes\CreateQuoteAction;
use App\Actions\Quotes\DuplicateQuoteAction;
use App\Enums\CatalogItemType;
use App\Enums\LeadStage;
use App\Enums\MeasurementUnit;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\CompanyProfile;
use App\Models\Lead;
use App\Models\MaintenanceContract;
use App\Models\QuoteTemplate;
use App\Models\Setting;
use App\Models\SlaTicket;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuarios
        $admin = User::create([
            'name' => 'Carlos Mendoza',
            'email' => 'admin@techcrm.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'phone' => '+51 999 100 200',
            'is_active' => true,
        ]);

        $comercial = User::create([
            'name' => 'Valeria Paredes',
            'email' => 'comercial@techcrm.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Comercial,
            'phone' => '+51 999 300 400',
            'is_active' => true,
        ]);

        $tecnico = User::create([
            'name' => 'Jorge Quispe',
            'email' => 'tecnico@techcrm.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Tecnico,
            'phone' => '+51 999 500 600',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Elena Navarro',
            'email' => 'supervisor@techcrm.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Supervisor,
            'phone' => '+51 999 700 800',
            'is_active' => true,
        ]);

        // Configuraciones corporativas por defecto
        Setting::setValue('min_margin_percent', '20', 'commercial');
        Setting::setValue('max_discount_percent', '10', 'commercial');
        Setting::setValue('company_name', 'TECHQUOTE INTEGRACIONES S.A.C.', 'company');
        Setting::setValue('company_ruc', '20601234567', 'company');
        Setting::setValue('company_address', 'Av. República de Panamá 3420, San Isidro, Lima', 'company');
        Setting::setValue('company_email', 'cotizaciones@techquote.pe', 'company');

        // Perfiles Multi-Empresa
        CompanyProfile::create([
            'name' => 'TECHQUOTE INTEGRACIONES S.A.C.',
            'ruc' => '20601234567',
            'address' => 'Av. República de Panamá 3420, San Isidro, Lima',
            'phone' => '(01) 442-8900',
            'email' => 'ventas@techquote.pe',
            'bank_accounts' => 'BCP Soles: 193-22445566-0-12 | BBVA USD: 0011-0123-0100045678',
            'is_default' => true,
            'is_active' => true,
        ]);

        CompanyProfile::create([
            'name' => 'TECHQUOTE NETWORKS & INFRAESTRUCTURA E.I.R.L.',
            'ruc' => '20609876543',
            'address' => 'Calle Los Pinos 190, Miraflores, Lima',
            'phone' => '(01) 445-7711',
            'email' => 'proyectos@techquotenetworks.pe',
            'bank_accounts' => 'Interbank Soles: 200-3004455667',
            'is_default' => false,
            'is_active' => true,
        ]);

        // 2. Categorías y 15 Ítems Técnicos
        $catCctv = CatalogCategory::create([
            'name' => 'Videovigilancia CCTV',
            'slug' => 'videovigilancia-cctv',
            'description' => 'Cámaras IP, grabadores NVR y almacenamiento especializado.',
        ]);

        $catCableado = CatalogCategory::create([
            'name' => 'Cableado Estructurado',
            'slug' => 'cableado-estructurado',
            'description' => 'Cables UTP Cat6/6A, patch panels, gabinetes y canalización.',
        ]);

        $catRedes = CatalogCategory::create([
            'name' => 'Redes y Wi-Fi Corporativo',
            'slug' => 'redes-wifi-corporativo',
            'description' => 'Switches PoE administrables, Access Points y routers empresariales.',
        ]);

        $catServicios = CatalogCategory::create([
            'name' => 'Servicios e Instalación',
            'slug' => 'servicios-instalacion',
            'description' => 'Mano de obra especializada, configuración y mantenimiento.',
        ]);

        $itemsData = [
            [$catCctv->id, 'CCTV-CAM-DOM4', 'Cámara IP Domo 4MP PoE IR 30m', 'Cámara IP interior/exterior IP67 con analítica básica y WDR real.', CatalogItemType::Equipo, MeasurementUnit::UND, 210.00, 340.00],
            [$catCctv->id, 'CCTV-CAM-BUL4', 'Cámara IP Bullet 4MP PoE Exterior', 'Cámara tipo bala IP67 con visión nocturna 40m y micrófono integrado.', CatalogItemType::Equipo, MeasurementUnit::UND, 235.00, 380.00],
            [$catCctv->id, 'CCTV-NVR-16CH', 'Grabador NVR 16 Canales 4K 2 Bahías', 'NVR H.265+ soporta hasta 16 cámaras IP 8MP y 2 discos SATA.', CatalogItemType::Equipo, MeasurementUnit::UND, 620.00, 950.00],
            [$catCctv->id, 'CCTV-HDD-4TB', 'Disco Duro WD Purple 4TB Videovigilancia', 'Almacenamiento optimizado para grabación continua 24/7.', CatalogItemType::Equipo, MeasurementUnit::UND, 360.00, 520.00],
            [$catCctv->id, 'CCTV-BAL-PAS', 'Kit Transceptor Video Balun Pasivo HD', 'Par de video balun para transmisión de energía y datos.', CatalogItemType::Material, MeasurementUnit::UND, 12.00, 25.00],
            [$catCableado->id, 'CAB-UTP-C6A', 'Bobina Cable UTP Cat6A LSZH 305m', 'Cable 100% cobre 23AWG libre de halógenos certificado.', CatalogItemType::Material, MeasurementUnit::CJA, 490.00, 740.00],
            [$catCableado->id, 'CAB-UTP-C6M', 'Cable UTP Cat6 100% Cobre (por metro)', 'Suministro fraccionado de cable UTP Cat6 certificado.', CatalogItemType::Material, MeasurementUnit::MTR, 1.45, 2.50],
            [$catCableado->id, 'CAB-PP-24P', 'Patch Panel 24 Puertos Cat6A Modular', 'Patch panel metálico 1RU con organizador posterior.', CatalogItemType::Equipo, MeasurementUnit::UND, 180.00, 290.00],
            [$catCableado->id, 'CAB-GAB-9RU', 'Gabinete Mural 9RU Puerta Vidrio', 'Gabinete de comunicaciones 600x450mm con ventilador y regleta.', CatalogItemType::Equipo, MeasurementUnit::UND, 290.00, 460.00],
            [$catCableado->id, 'CAB-JK-C6A', 'Jack RJ45 Cat6A + Faceplate + Caja', 'Kit completo de punto de red certificado.', CatalogItemType::Material, MeasurementUnit::UND, 24.00, 45.00],
            [$catRedes->id, 'RED-SW-16POE', 'Switch PoE+ 16 Puertos Gigabit + 2 SFP', 'Switch administrable Capa 2 con presupuesto 250W PoE.', CatalogItemType::Equipo, MeasurementUnit::UND, 780.00, 1190.00],
            [$catRedes->id, 'RED-AP-WIFI6', 'Access Point Wi-Fi 6 Empresarial Dual Band', 'AP techo/pared AX3000 PoE+ con gestión centralizada.', CatalogItemType::Equipo, MeasurementUnit::UND, 380.00, 590.00],
            [$catServicios->id, 'SRV-INS-PTO', 'Servicio Instalación Punto de Red', 'Tendido, canalización, ponchado, etiquetado y certificación.', CatalogItemType::Servicio, MeasurementUnit::PTO, 55.00, 120.00],
            [$catServicios->id, 'SRV-INS-CAM', 'Instalación y Configuración Cámara IP', 'Montaje en altura, direccionamiento, enfoque y alta en NVR/App.', CatalogItemType::Servicio, MeasurementUnit::UND, 65.00, 140.00],
            [$catServicios->id, 'SRV-MNT-CCTV', 'Mantenimiento Preventivo Sistema CCTV', 'Limpieza de lentes, revisión de fuentes, conectores y respaldo NVR.', CatalogItemType::Mantenimiento, MeasurementUnit::GLB, 280.00, 550.00],
        ];

        $catalog = [];
        foreach ($itemsData as [$catId, $sku, $name, $desc, $type, $unit, $cost, $price]) {
            $catalog[$sku] = CatalogItem::create([
                'category_id' => $catId,
                'sku' => $sku,
                'name' => $name,
                'description' => $desc,
                'item_type' => $type,
                'unit' => $unit,
                'unit_cost' => $cost,
                'sale_price' => $price,
                'tax_rate' => 18.00,
                'is_active' => true,
            ]);
        }

        // 3. 4 Clientes Corporativos con múltiples sedes y contactos
        $client1 = Client::create([
            'document_type' => 'RUC',
            'document_number' => '20512345678',
            'business_name' => 'Logística Integral del Pacífico S.A.C.',
            'trade_name' => 'LogiPacífico',
            'sector' => 'Logística y Almacenes',
            'fiscal_address' => 'Av. Argentina 2840, Callao',
            'phone' => '(01) 520-1100',
            'email' => 'compras@logipacifico.pe',
            'assigned_user_id' => $comercial->id,
        ]);
        $c1Contact1 = $client1->contacts()->create(['full_name' => 'Roberto Salazar', 'position' => 'Jefe de TI', 'email' => 'rsalazar@logipacifico.pe', 'phone' => '987654321', 'is_primary' => true]);
        $client1->contacts()->create(['full_name' => 'Lucía Fernández', 'position' => 'Analista de Compras', 'email' => 'lfernandez@logipacifico.pe', 'phone' => '987654322', 'is_primary' => false]);
        $c1Site1 = $client1->sites()->create(['site_name' => 'Almacén Central Callao', 'address' => 'Av. Néstor Gambetta 4500', 'district_city' => 'Callao', 'reference' => 'Frente al terminal portuario']);
        $c1Site2 = $client1->sites()->create(['site_name' => 'Oficinas Administrativas', 'address' => 'Av. Pardo y Aliaga 640', 'district_city' => 'San Isidro, Lima', 'reference' => 'Piso 7']);

        $client2 = Client::create([
            'document_type' => 'RUC',
            'document_number' => '20604567891',
            'business_name' => 'Clínica Médica San Gabriel S.A.',
            'trade_name' => 'Clínica San Gabriel',
            'sector' => 'Salud',
            'fiscal_address' => 'Av. Javier Prado Este 1420, San Borja',
            'phone' => '(01) 475-3300',
            'email' => 'infraestructura@clinicasangabriel.pe',
            'assigned_user_id' => $comercial->id,
        ]);
        $c2Contact1 = $client2->contacts()->create(['full_name' => 'Dra. Patricia Morales', 'position' => 'Gerente de Operaciones', 'email' => 'pmorales@clinicasangabriel.pe', 'phone' => '991223344', 'is_primary' => true]);
        $c2Site1 = $client2->sites()->create(['site_name' => 'Sede Principal San Borja', 'address' => 'Av. Javier Prado Este 1420', 'district_city' => 'San Borja, Lima', 'reference' => 'Torre B']);
        $client2->sites()->create(['site_name' => 'Centro Diagnóstico Surco', 'address' => 'Av. Caminos del Inca 890', 'district_city' => 'Santiago de Surco, Lima']);

        $client3 = Client::create([
            'document_type' => 'RUC',
            'document_number' => '20488776655',
            'business_name' => 'Industrias Alimentarias Andinas S.R.L.',
            'trade_name' => 'Alimentos Andinos',
            'sector' => 'Manufactura',
            'fiscal_address' => 'Carretera Central Km 14.5, Ate',
            'phone' => '(01) 351-9900',
            'email' => 'mantenimiento@alimentosandinos.pe',
            'assigned_user_id' => $admin->id,
        ]);
        $c3Contact1 = $client3->contacts()->create(['full_name' => 'Ing. Miguel Torres', 'position' => 'Jefe de Planta', 'email' => 'mtorres@alimentosandinos.pe', 'phone' => '998877665', 'is_primary' => true]);
        $c3Site1 = $client3->sites()->create(['site_name' => 'Planta Procesadora Ate', 'address' => 'Carretera Central Km 14.5', 'district_city' => 'Ate, Lima']);

        $client4 = Client::create([
            'document_type' => 'RUC',
            'document_number' => '20609988776',
            'business_name' => 'Estudio Jurídico Vega & Asociados S.C.R.L.',
            'trade_name' => 'Vega & Asociados',
            'sector' => 'Servicios Profesionales',
            'fiscal_address' => 'Calle Las Begonias 475, San Isidro',
            'phone' => '(01) 422-7788',
            'email' => 'administracion@vegaasociados.pe',
            'assigned_user_id' => $comercial->id,
        ]);
        $c4Contact1 = $client4->contacts()->create(['full_name' => 'Sofía Vega', 'position' => 'Socia Administradora', 'email' => 'svega@vegaasociados.pe', 'phone' => '994556677', 'is_primary' => true]);
        $c4Site1 = $client4->sites()->create(['site_name' => 'Oficina San Isidro', 'address' => 'Calle Las Begonias 475, Of. 902', 'district_city' => 'San Isidro, Lima']);

        // 4. 6 Leads distribuidos en el pipeline con visitas técnicas
        $lead1 = Lead::create([
            'code' => 'OPR-2026-0001',
            'client_id' => $client1->id,
            'site_id' => $c1Site1->id,
            'assigned_user_id' => $comercial->id,
            'technician_id' => $tecnico->id,
            'title' => 'Sistema CCTV 12 Cámaras IP Almacén Callao',
            'service_line' => 'cctv',
            'stage' => LeadStage::Cotizado,
            'source' => 'cliente_recurrente',
            'estimated_amount' => 11500.00,
            'expected_close_date' => now()->addDays(10)->toDateString(),
            'notes' => 'Requiere cobertura en puertas de carga y pasillos principales.',
        ]);
        $lead1->activities()->create([
            'user_id' => $tecnico->id,
            'activity_type' => 'visita_tecnica',
            'subject' => 'Levantamiento técnico Almacén Central',
            'description' => 'Metraje estimado: 420m de cable UTP Cat6A con tubería EMT 3/4" a 5m de altura. Gabinete 9RU en garita de vigilancia.',
            'completed_at' => now()->subDays(3),
        ]);

        $lead2 = Lead::create([
            'code' => 'OPR-2026-0002',
            'client_id' => $client2->id,
            'site_id' => $c2Site1->id,
            'assigned_user_id' => $comercial->id,
            'technician_id' => $tecnico->id,
            'title' => 'Cableado Estructurado 24 Puntos y Wi-Fi 6 Torre B',
            'service_line' => 'cableado_estructurado',
            'stage' => LeadStage::Negociacion,
            'source' => 'referido',
            'estimated_amount' => 9800.00,
            'expected_close_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Cliente evaluando propuesta v2 con descuento corporativo.',
        ]);
        $lead2->activities()->create([
            'user_id' => $tecnico->id,
            'activity_type' => 'visita_tecnica',
            'subject' => 'Inspección de ductos y falso techo Torre B',
            'description' => 'Se verificó disponibilidad de bandeja portacables existente. Se requieren 24 puntos Cat6A y 3 Access Points Wi-Fi 6.',
            'completed_at' => now()->subDays(5),
        ]);

        $lead3 = Lead::create([
            'code' => 'OPR-2026-0003',
            'client_id' => $client3->id,
            'site_id' => $c3Site1->id,
            'assigned_user_id' => $admin->id,
            'technician_id' => $tecnico->id,
            'title' => 'Mantenimiento Preventivo Anual CCTV Planta Ate',
            'service_line' => 'mantenimiento',
            'stage' => LeadStage::Ganado,
            'source' => 'llamada',
            'estimated_amount' => 3200.00,
            'expected_close_date' => now()->subDays(1)->toDateString(),
        ]);

        $lead4 = Lead::create([
            'code' => 'OPR-2026-0004',
            'client_id' => $client4->id,
            'site_id' => $c4Site1->id,
            'assigned_user_id' => $comercial->id,
            'technician_id' => $tecnico->id,
            'title' => 'Red Wi-Fi Corporativa y Switch PoE Oficinas',
            'service_line' => 'redes_wifi',
            'stage' => LeadStage::VisitaTecnica,
            'source' => 'web',
            'estimated_amount' => 4500.00,
            'expected_close_date' => now()->addDays(15)->toDateString(),
        ]);
        $lead4->activities()->create([
            'user_id' => $tecnico->id,
            'activity_type' => 'visita_tecnica',
            'subject' => 'Visita técnica programada para medición de cobertura RF',
            'description' => 'Evaluación de muros de drywall y vidrio templado en piso 9.',
            'scheduled_at' => now()->addDays(1),
        ]);

        Lead::create([
            'code' => 'OPR-2026-0005',
            'client_id' => $client1->id,
            'site_id' => $c1Site2->id,
            'assigned_user_id' => $comercial->id,
            'title' => 'Ampliación 8 Puntos de Red Sala de Directorio',
            'service_line' => 'cableado_estructurado',
            'stage' => LeadStage::Nuevo,
            'source' => 'web',
            'estimated_amount' => 2400.00,
            'expected_close_date' => now()->addDays(20)->toDateString(),
        ]);

        Lead::create([
            'code' => 'OPR-2026-0006',
            'client_id' => $client3->id,
            'site_id' => $c3Site1->id,
            'assigned_user_id' => $admin->id,
            'title' => 'Proyecto Integral Cámaras Térmicas Perimetrales',
            'service_line' => 'integral',
            'stage' => LeadStage::Perdido,
            'source' => 'licitacion',
            'estimated_amount' => 28000.00,
            'lost_reason' => 'Presupuesto postergado para el próximo trimestre.',
        ]);

        // 5. 4 Cotizaciones completas (incluyendo una con v1 y v2)
        $createQuote = app(CreateQuoteAction::class);
        $duplicateQuote = app(DuplicateQuoteAction::class);
        $changeStatus = app(ChangeQuoteStatusAction::class);

        $q1 = $createQuote->execute([
            'client_id' => $client1->id,
            'contact_id' => $c1Contact1->id,
            'site_id' => $c1Site1->id,
            'lead_id' => $lead1->id,
            'currency' => 'PEN',
            'payment_terms' => '50% adelanto, 50% contra entrega y conformidad',
            'delivery_time' => '10 días hábiles',
            'warranty_terms' => '12 meses en equipos e instalación',
            'technical_notes' => 'Incluye configuración de visualización remota en móviles y PC de garita.',
            'items' => [
                ['catalog_item_id' => $catalog['CCTV-CAM-BUL4']->id, 'section_name' => '1. Equipamiento CCTV', 'description' => $catalog['CCTV-CAM-BUL4']->name, 'unit' => 'UND', 'quantity' => 8, 'unit_cost' => 235, 'unit_price' => 380, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['CCTV-CAM-DOM4']->id, 'section_name' => '1. Equipamiento CCTV', 'description' => $catalog['CCTV-CAM-DOM4']->name, 'unit' => 'UND', 'quantity' => 4, 'unit_cost' => 210, 'unit_price' => 340, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['CCTV-NVR-16CH']->id, 'section_name' => '1. Equipamiento CCTV', 'description' => $catalog['CCTV-NVR-16CH']->name, 'unit' => 'UND', 'quantity' => 1, 'unit_cost' => 620, 'unit_price' => 950, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['CCTV-HDD-4TB']->id, 'section_name' => '1. Equipamiento CCTV', 'description' => $catalog['CCTV-HDD-4TB']->name, 'unit' => 'UND', 'quantity' => 2, 'unit_cost' => 360, 'unit_price' => 520, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['CAB-UTP-C6A']->id, 'section_name' => '2. Cableado y Materiales', 'description' => $catalog['CAB-UTP-C6A']->name, 'unit' => 'CJA', 'quantity' => 2, 'unit_cost' => 490, 'unit_price' => 740, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['CAB-GAB-9RU']->id, 'section_name' => '2. Cableado y Materiales', 'description' => $catalog['CAB-GAB-9RU']->name, 'unit' => 'UND', 'quantity' => 1, 'unit_cost' => 290, 'unit_price' => 460, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['SRV-INS-CAM']->id, 'section_name' => '3. Servicios de Instalación', 'description' => $catalog['SRV-INS-CAM']->name, 'unit' => 'UND', 'quantity' => 12, 'unit_cost' => 65, 'unit_price' => 140, 'discount_percent' => 0],
            ],
        ], $comercial);
        $changeStatus->execute($q1, QuoteStatus::Enviada, $comercial, 'Enviada al Jefe de TI por correo.');

        // Cotización 2 (v1 y v2)
        $q2v1 = $createQuote->execute([
            'client_id' => $client2->id,
            'contact_id' => $c2Contact1->id,
            'site_id' => $c2Site1->id,
            'lead_id' => $lead2->id,
            'currency' => 'PEN',
            'items' => [
                ['catalog_item_id' => $catalog['CAB-UTP-C6A']->id, 'section_name' => '1. Cableado Estructurado', 'description' => $catalog['CAB-UTP-C6A']->name, 'unit' => 'CJA', 'quantity' => 3, 'unit_cost' => 490, 'unit_price' => 740, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['CAB-PP-24P']->id, 'section_name' => '1. Cableado Estructurado', 'description' => $catalog['CAB-PP-24P']->name, 'unit' => 'UND', 'quantity' => 1, 'unit_cost' => 180, 'unit_price' => 290, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['RED-SW-16POE']->id, 'section_name' => '2. Equipamiento de Red', 'description' => $catalog['RED-SW-16POE']->name, 'unit' => 'UND', 'quantity' => 1, 'unit_cost' => 780, 'unit_price' => 1190, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['RED-AP-WIFI6']->id, 'section_name' => '2. Equipamiento de Red', 'description' => $catalog['RED-AP-WIFI6']->name, 'unit' => 'UND', 'quantity' => 3, 'unit_cost' => 380, 'unit_price' => 590, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['SRV-INS-PTO']->id, 'section_name' => '3. Mano de Obra', 'description' => $catalog['SRV-INS-PTO']->name, 'unit' => 'PTO', 'quantity' => 24, 'unit_cost' => 55, 'unit_price' => 120, 'discount_percent' => 0],
            ],
        ], $comercial);
        $changeStatus->execute($q2v1, QuoteStatus::Rechazada, $comercial, 'Cliente solicitó ajuste de descuento por volumen.');

        $q2v2 = $duplicateQuote->execute($q2v1, $comercial);
        $q2v2->update(['discount_amount' => 450.00]);
        $changeStatus->execute($q2v2, QuoteStatus::Enviada, $comercial, 'Versión v2 con descuento corporativo aplicado.');

        // Cotización 3 (Aprobada)
        $q3 = $createQuote->execute([
            'client_id' => $client3->id,
            'contact_id' => $c3Contact1->id,
            'site_id' => $c3Site1->id,
            'lead_id' => $lead3->id,
            'currency' => 'PEN',
            'items' => [
                ['catalog_item_id' => $catalog['SRV-MNT-CCTV']->id, 'section_name' => '1. Mantenimiento Preventivo', 'description' => 'Mantenimiento Preventivo Trimestral Sistema CCTV (24 cámaras)', 'unit' => 'GLB', 'quantity' => 4, 'unit_cost' => 280, 'unit_price' => 550, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['CCTV-HDD-4TB']->id, 'section_name' => '2. Repuestos', 'description' => $catalog['CCTV-HDD-4TB']->name, 'unit' => 'UND', 'quantity' => 1, 'unit_cost' => 360, 'unit_price' => 520, 'discount_percent' => 0],
            ],
        ], $admin);
        $changeStatus->execute($q3, QuoteStatus::Aprobada, $admin, 'Orden de servicio aprobada por Gerencia de Planta.');

        // Cotización 4 (Borrador USD)
        $createQuote->execute([
            'client_id' => $client4->id,
            'contact_id' => $c4Contact1->id,
            'site_id' => $c4Site1->id,
            'lead_id' => $lead4->id,
            'currency' => 'USD',
            'exchange_rate' => 3.7500,
            'items' => [
                ['catalog_item_id' => $catalog['RED-AP-WIFI6']->id, 'section_name' => '1. Conectividad Wi-Fi 6', 'description' => $catalog['RED-AP-WIFI6']->name, 'unit' => 'UND', 'quantity' => 2, 'unit_cost' => 105, 'unit_price' => 165, 'discount_percent' => 0],
                ['catalog_item_id' => $catalog['SRV-INS-PTO']->id, 'section_name' => '2. Instalación', 'description' => $catalog['SRV-INS-PTO']->name, 'unit' => 'PTO', 'quantity' => 4, 'unit_cost' => 15, 'unit_price' => 35, 'discount_percent' => 0],
            ],
        ], $comercial);

        // 6. Plantillas BOM (Kits de Soluciones)
        $tplCctv = QuoteTemplate::create([
            'name' => 'Kit CCTV 8 Cámaras IP 4MP + NVR + Instalación',
            'service_line' => 'cctv',
            'description' => 'Solución llave en mano para almacenes o oficinas medianas.',
            'created_by' => $admin->id,
        ]);
        $tplCctv->items()->createMany([
            ['catalog_item_id' => $catalog['CCTV-CAM-BUL4']->id, 'section_name' => '1. Equipamiento CCTV', 'description' => $catalog['CCTV-CAM-BUL4']->name, 'unit' => 'UND', 'quantity' => 4, 'unit_cost' => 235, 'unit_price' => 380, 'sort_order' => 0],
            ['catalog_item_id' => $catalog['CCTV-CAM-DOM4']->id, 'section_name' => '1. Equipamiento CCTV', 'description' => $catalog['CCTV-CAM-DOM4']->name, 'unit' => 'UND', 'quantity' => 4, 'unit_cost' => 210, 'unit_price' => 340, 'sort_order' => 1],
            ['catalog_item_id' => $catalog['CCTV-NVR-16CH']->id, 'section_name' => '1. Equipamiento CCTV', 'description' => $catalog['CCTV-NVR-16CH']->name, 'unit' => 'UND', 'quantity' => 1, 'unit_cost' => 620, 'unit_price' => 950, 'sort_order' => 2],
            ['catalog_item_id' => $catalog['CCTV-HDD-4TB']->id, 'section_name' => '1. Equipamiento CCTV', 'description' => $catalog['CCTV-HDD-4TB']->name, 'unit' => 'UND', 'quantity' => 1, 'unit_cost' => 360, 'unit_price' => 520, 'sort_order' => 3],
            ['catalog_item_id' => $catalog['CAB-UTP-C6A']->id, 'section_name' => '2. Cableado y Materiales', 'description' => $catalog['CAB-UTP-C6A']->name, 'unit' => 'CJA', 'quantity' => 1, 'unit_cost' => 490, 'unit_price' => 740, 'sort_order' => 4],
            ['catalog_item_id' => $catalog['SRV-INS-CAM']->id, 'section_name' => '3. Servicios de Instalación', 'description' => $catalog['SRV-INS-CAM']->name, 'unit' => 'UND', 'quantity' => 8, 'unit_cost' => 65, 'unit_price' => 140, 'sort_order' => 5],
        ]);

        $tplCab = QuoteTemplate::create([
            'name' => 'Kit Cableado Estructurado 24 Puntos Cat6A + Gabinete',
            'service_line' => 'cableado_estructurado',
            'description' => 'Infraestructura certificada Cat6A para 24 estaciones de trabajo.',
            'created_by' => $admin->id,
        ]);
        $tplCab->items()->createMany([
            ['catalog_item_id' => $catalog['CAB-UTP-C6A']->id, 'section_name' => '1. Cableado Estructurado', 'description' => $catalog['CAB-UTP-C6A']->name, 'unit' => 'CJA', 'quantity' => 3, 'unit_cost' => 490, 'unit_price' => 740, 'sort_order' => 0],
            ['catalog_item_id' => $catalog['CAB-PP-24P']->id, 'section_name' => '1. Cableado Estructurado', 'description' => $catalog['CAB-PP-24P']->name, 'unit' => 'UND', 'quantity' => 1, 'unit_cost' => 180, 'unit_price' => 290, 'sort_order' => 1],
            ['catalog_item_id' => $catalog['CAB-GAB-9RU']->id, 'section_name' => '1. Cableado Estructurado', 'description' => $catalog['CAB-GAB-9RU']->name, 'unit' => 'UND', 'quantity' => 1, 'unit_cost' => 290, 'unit_price' => 460, 'sort_order' => 2],
            ['catalog_item_id' => $catalog['CAB-JK-C6A']->id, 'section_name' => '1. Cableado Estructurado', 'description' => $catalog['CAB-JK-C6A']->name, 'unit' => 'UND', 'quantity' => 24, 'unit_cost' => 24, 'unit_price' => 45, 'sort_order' => 3],
            ['catalog_item_id' => $catalog['SRV-INS-PTO']->id, 'section_name' => '2. Servicios de Instalación', 'description' => $catalog['SRV-INS-PTO']->name, 'unit' => 'PTO', 'quantity' => 24, 'unit_cost' => 55, 'unit_price' => 120, 'sort_order' => 4],
        ]);
        // 7. Almacén, Stock Inicial y Proveedores (Fase 3)
        $warehouse = Warehouse::create([
            'name' => 'Almacén Central San Isidro',
            'location' => 'Av. República de Panamá 3420, Lima',
            'is_active' => true,
        ]);

        foreach (['CCTV-CAM-DOM4' => 24, 'CCTV-CAM-BUL4' => 18, 'CCTV-NVR-16CH' => 6, 'CCTV-HDD-4TB' => 10, 'CAB-UTP-C6A' => 12, 'RED-SW-16POE' => 5, 'RED-AP-WIFI6' => 14] as $sku => $qty) {
            if (isset($catalog[$sku])) {
                WarehouseStock::create([
                    'warehouse_id' => $warehouse->id,
                    'catalog_item_id' => $catalog[$sku]->id,
                    'quantity' => $qty,
                    'reserved_quantity' => 0,
                    'min_stock' => 4,
                ]);
            }
        }

        Supplier::create([
            'ruc' => '20554433221',
            'business_name' => 'Mayorista Seguridad Electrónica Perú S.A.C.',
            'contact_name' => 'Marco Rojas',
            'email' => 'ventas@mayoristaseguridad.pe',
            'phone' => '(01) 421-9000',
        ]);

        Supplier::create([
            'ruc' => '20511223344',
            'business_name' => 'Conectividad & Cableado del Sur S.R.L.',
            'contact_name' => 'Carla Díaz',
            'email' => 'pedidos@conectividadsur.pe',
            'phone' => '(01) 340-5500',
        ]);

        // 8. Contrato SLA y Ticket Inicial (Fase 4)
        $contract = MaintenanceContract::create([
            'contract_number' => 'CTR-2026-0001',
            'client_id' => $client3->id,
            'site_id' => $c3Site1->id,
            'title' => 'Póliza Mantenimiento Preventivo CCTV 24 Cámaras',
            'frequency' => 'trimestral',
            'monthly_fee' => 850.00,
            'currency' => 'PEN',
            'sla_hours' => 12,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
            'next_visit_date' => now()->addDays(14)->toDateString(),
            'status' => 'activo',
        ]);

        SlaTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'contract_id' => $contract->id,
            'client_id' => $client3->id,
            'assigned_to' => $tecnico->id,
            'priority' => 'alta',
            'status' => 'en_proceso',
            'subject' => 'Cámara perimetral #4 sin señal nocturna',
            'description' => 'Revisar fuente PoE y conector RJ45 en poste norte.',
            'due_at' => now()->addHours(10),
        ]);
    }
}
