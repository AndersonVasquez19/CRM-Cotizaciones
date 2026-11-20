# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users
- **Internal Operations Team (Primary — Operate):** Ejecutivos comerciales, ingenieros/técnicos de campo, supervisores y administradores en empresas integradoras de tecnología (CCTV/Videovigilancia, Cableado Estructurado, Redes/Wi-Fi Corporativo y Mantenimiento). Trabajan diariamente desde oficina y campo en laptop/desktop y tablet.
- **External B2B Clients (Secondary — Read & Sign):** Gerentes y compradores de empresas cliente que reciben enlaces públicos (`/p/quotes/{token}`) para revisar propuestas técnicas, descargar PDFs y aprobar o rechazar con firma digital.

## Product Purpose
TechQuote CRM unifica el ciclo comercial, técnico y operativo de una empresa integradora de tecnología: desde la prospección y levantamiento técnico (Site Survey) hasta la cotización con control de márgenes, aprobación digital del cliente, ejecución de proyectos, inventario, contratos SLA y control interno de cobros.

## Positioning
Motor de cotizaciones técnicas B2B con control de rentabilidad por partida, flujo de aprobación de márgenes por rol, kits BOM preconfigurados, portal público con firma digital y conversión automática a órdenes de trabajo/proyectos en un solo monorepo conectado a datos reales.

## Operating Context
- **Entorno diario:** Tablero Kanban comercial, constructor de cotizaciones con cálculos en tiempo real (IGV, márgenes, moneda PEN/USD, multi-empresa), registro fotográfico de levantamientos técnicos, gestión de proyectos con números de serie, almacén/compras, contratos SLA y reportes BI.
- **Rituales clave:** Revisión matinal de KPIs y pipeline en Dashboard; cotización rápida en oficina/campo; aprobación de descuentos/márgenes por supervisores; firma de conformidad de obra y seguimiento de cobros internos.

## Capabilities and Constraints
- **Idioma:** Español (Perú / LATAM B2B, monedas PEN `S/` y USD `$`, IGV 18%).
- **Integridad de datos:** 100% conectado a `/api/v1` (Laravel 13 + MySQL); cero mocks o datos inventados.
- **RBAC:** Roles `admin`, `comercial`, `tecnico`, `supervisor` (`tecnico` no visualiza costos unitarios ni márgenes).
- **Alcance de rediseño:** Renovación visual integral y coherente en Layout/Sidebar, Login, Dashboard, Leads Kanban, Cotizador, Portal Público y módulos operativos (Proyectos, Inventario, Contratos/SLA, Finanzas, Reportes, Usuarios, Configuración).

## Brand Commitments
- **Nombre del producto:** TechQuote CRM.
- **Multi-empresa:** Soporte para múltiples razones sociales emisoras (`company_profiles`) en cotizaciones y PDFs.

## Evidence on Hand
- Monorepo operativo en `C:\Users\PCT40T\Desktop\techquote-crm` (`/backend` Laravel 13 + `/frontend` Nuxt 4 + Vue 3 + Tailwind CSS).
- Base de datos real `servicios_tecnologicos` con seeders y 14 suites Pest en verde.

## Product Principles
1. **Claridad operativa bajo densidad:** Tablas, Kanban y cotizadores deben mostrar jerarquía numérica inmediata (`tabular-nums`), estados inequívocos y acciones primarias sin ruido visual.
2. **Precisión técnica por encima de la ornamentación:** La estética debe evocar ingeniería, infraestructura crítica y confiabilidad B2B, evitando plantillas SaaS genéricas.
3. **Coherencia de punta a punta:** Desde el Login y el Sidebar hasta el Portal Público del cliente, cada superficie comparte lenguaje, ritmo y rigor tipográfico.
4. **Cero fricción en flujos críticos:** Cotizar, mover etapas en Kanban, aprobar márgenes o firmar digitalmente debe requerir los mínimos clics posibles.
