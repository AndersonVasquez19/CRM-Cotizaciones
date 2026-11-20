# Design System — TechQuote CRM

## Visual World: Tactical Network & Optical Precision
TechQuote CRM usa un sistema visual bitonal de alto contraste inspirado en consolas de videovigilancia VMS y analizadores de redes corporativas: riel lateral oscuro obsidiana (`#0B0F14`), superficie de trabajo luminosa y espaciosa (`#F3F5F8` / `#FFFFFF`), bordes estructurales nítidos de `1px` sin sombras difusas genéricas, y acento **Signal Cyan (`#0891B2`)** para selección y acciones primarias.

## Color Tokens (`frontend/app/assets/css/main.css`)
- **Rail / Shell oscuro:** `--tq-rail: #0B0F14`, `--tq-rail-elevated: #141B23`, `--tq-rail-border: #1E2733`
- **Canvas & Superficies:** `--tq-canvas: #F3F5F8`, `--tq-surface: #FFFFFF`, `--tq-surface-subtle: #F8FAFC`
- **Bordes:** `--tq-border: #D8DFE8`, `--tq-border-strong: #BCC7D6`
- **Texto:** `--tq-ink: #0F172A`, `--tq-ink-muted: #526071`
- **Acento primario:** `--tq-accent: #0891B2` (`#0E7490` hover, `#ECFEFF` soft)
- **Estados semánticos:** `--tq-emerald: #059669` (aprobado/saludable), `--tq-amber: #D97706` (alerta margen/pendiente), `--tq-crimson: #DC2626` (crítico/rechazado)

## Typography
- **UI & Headings:** `Public Sans` (400, 500, 600, 700)
- **Códigos y Cifras Tabulares:** `JetBrains Mono` (`.tq-code`) para identificadores (`COT-`, `OPR-`, `PRY-`, `CTR-`, `TKT-`) y `font-variant-numeric: tabular-nums` (`.tq-num`) en tablas, KPIs y totales.

## Core Primitives (`main.css`)
- `.tq-panel` & `.tq-panel-header`: contenedores con borde `1px solid var(--tq-border)` y radio de `6px`, sin sombras genéricas.
- `.tq-btn-primary` & `.tq-btn-secondary`: botones con radio `5px` y transición `160ms ease`.
- `.tq-table`: tablas con encabezados compactos sobre `--tq-surface-subtle` y filas con alineación tabular.
- `.tq-input`: controles de formulario con `caret-color: var(--tq-accent)` y anillo de foco `:focus-visible` de `2px solid var(--tq-accent)`.

## Layout & Navigation
- **App Shell (`layouts/default.vue`):** Sidebar izquierdo fijo de `256px` en `#0B0F14` con navegación agrupada (`Comercial`, `Ejecución & Campo`, `Control & Gerencia`), barra superior de `56px` con breadcrumb y CTA `+ Nueva Cotización`.
- **Auth (`pages/login.vue`):** Pantalla dividida con panel arquitectónico oscuro + retícula técnica SVG a la izquierda y formulario de acceso a la derecha.
- **Portal Público (`pages/p/quotes/[token].vue`):** Cabecera `#0B0F14`, tabla de partidas con totales tabulares y pad de firma digital en canvas.
