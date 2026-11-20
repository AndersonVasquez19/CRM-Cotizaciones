# AGENTS.md — TechQuote CRM Monorepo

Convenciones técnicas y reglas de arquitectura para agentes y desarrolladores que trabajen en este repositorio.

## Estructura del Monorepo
- `/backend`: API RESTful v1 en **Laravel 13** (PHP 8.3+) + **Sanctum** + **DomPDF** + **Pest**.
- `/frontend`: SPA en **Nuxt 4** (`ssr: false`, Vue 3 + TypeScript estricto + TailwindCSS + Pinia).

## Reglas Backend (`/backend`)
1. **Base de datos y drivers**: MySQL (`servicios_tecnologicos`). Prohibido Redis; usar `database` para `CACHE_STORE`, `SESSION_DRIVER` y `QUEUE_CONNECTION`.
2. **Lógica de negocio**: Encapsulada en `app/Actions/` (`Quotes/*`, `Crm/*`) y `app/Services/`. Los controladores en `app/Http/Controllers/Api/V1/` solo validan entrada, invocan la Action/Service y devuelven JSON.
3. **Enums**: Todos los estados y catálogos cerrados viven en `app/Enums/` con `label(): string` y `values(): array`.
4. **Cálculos financieros**: Todo cálculo de partidas, descuentos, IGV (18%) y margen bruto debe pasar por `CalculateQuoteTotalsAction` con redondeo a 2 decimales.
5. **Pruebas**: Toda nueva funcionalidad o corrección debe cubrirse con tests en Pest (`tests/Unit` y `tests/Feature`). Ejecutar `php artisan test` antes de finalizar.

## Reglas Frontend (`/frontend`)
1. **Cero datos mock**: Toda vista consume `/api/v1` mediante `useApi()`.
2. **Tipado estricto**: Todas las entidades y respuestas paginadas deben declararse en `app/types/index.ts`. Ejecutar `npx nuxi typecheck` sin errores.
3. **Autenticación**: Token Bearer almacenado en `useCookie('auth_token')`, gestionado por `useAuthStore` y protegido por `app/middleware/auth.global.ts`.
