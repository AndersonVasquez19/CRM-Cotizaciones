# TechQuote CRM — CRM Empresarial y Motor de Cotizaciones Técnicas

Monorepo B2B diseñado para empresas integradoras de tecnología (**CCTV/Videovigilancia**, **Cableado Estructurado**, **Redes/Wi-Fi Corporativo** y **Mantenimiento**).

## Arquitectura
- **`/backend`**: API RESTful `/api/v1` en **Laravel 13** + **Sanctum** + **DomPDF** + **Pest** sobre MySQL (`servicios_tecnologicos`).
- **`/frontend`**: Aplicación SPA en **Nuxt** (Vue 3, TypeScript, TailwindCSS, Pinia, Lucide Icons).

## Credenciales Demo
| Rol | Correo | Contraseña |
| :--- | :--- | :--- |
| Administrador | `admin@techcrm.com` | `password` |
| Ejecutivo Comercial | `comercial@techcrm.com` | `password` |
| Especialista Técnico | `tecnico@techcrm.com` | `password` |

*(La pantalla `/login` incluye botones de acceso rápido Demo).*

## Comandos de Arranque

### 1. Backend (`/backend`)
```bash
cd backend
php artisan migrate:fresh --seed
php artisan serve --port=8000
```
Pruebas automatizadas (Pest):
```bash
cd backend
php artisan test
```

### 2. Frontend (`/frontend`)
```bash
cd frontend
npm run dev
```
La aplicación estará disponible en `http://localhost:3000` conectada a `http://localhost:8000/api/v1`.

Verificación de tipos:
```bash
cd frontend
npx nuxi typecheck
```
