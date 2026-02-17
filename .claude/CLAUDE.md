# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sistema de Rendiciones - an expense accountability management system (rendiciones de gastos y viaticos). Users register expenses, attach receipts, scan invoice QR codes (SIAT Bolivia), and submit for review/approval. Built with Laravel 10 + Vue 3 + Quasar + Inertia.js as a single-page application.

## Tech Stack

- **Backend:** Laravel 10 (PHP 8.1+)
- **Frontend:** Vue 3 + Quasar 2 (UI framework) + Inertia.js (SPA bridge)
- **Build:** Vite with laravel-vite-plugin, vite-svg-loader
- **Package Manager:** pnpm (lockfile: `pnpm-lock.yaml`)
- **Database:** SQL Server (primary, `sqlsrv`), with secondary SAP connection (`sap`) and optional SAP HANA via ODBC
- **Routing (client):** Ziggy (aliased in vite.config.js as `vendor/tightenco/ziggy`)
- **Templates:** Vue SFC files use `<script setup>` composition API; some use Pug templates
- **Styling:** SCSS in Vue components; Quasar component classes; Sass variables in `resources/css/quasar-variables.sass`
- **PDF generation:** barryvdh/laravel-dompdf (Blade templates in `resources/views/pdf/`)
- **QR Scanning:** vue-qrcode-reader (QrcodeStream component for camera-based invoice scanning)
- **Animations:** lottie-web + @lordicon/element (custom element registered in `app.js`)
- **Linting:** Laravel Pint (PHP code style)

## Common Commands

```bash
# Development server (frontend)
pnpm dev

# Build frontend for production
pnpm build

# Install dependencies
pnpm install

# PHP linting
./vendor/bin/pint

# Run tests
php artisan test
./vendor/bin/phpunit                    # all tests
./vendor/bin/phpunit tests/Unit         # unit tests only
./vendor/bin/phpunit tests/Feature      # feature tests only
./vendor/bin/phpunit --filter=TestName  # single test

# Laravel common commands
php artisan migrate
php artisan tinker
php artisan route:list
```

## Architecture

### Inertia.js SPA Pattern

This is **not** a traditional API + SPA. Inertia.js bridges Laravel controllers and Vue pages:
- Controllers return `Inertia::render('page/name', [...props])` instead of JSON or Blade views
- Vue pages live in `resources/js/pages/` and are resolved via `resolvePageComponent('./pages/${name}.vue', import.meta.glob('./pages/**/*.vue'))`
- Navigation uses `router.visit(route('named.route'))` (Inertia router + Ziggy named routes)
- Shared data (auth user, flash messages, errors) is passed via `HandleInertiaRequests` middleware

### Dual Database / SAP Integration

The app connects to two databases:
- **Primary (`sqlsrv`):** Application data (users, accountabilities, profiles, documents)
- **SAP (`sap` or `hana`):** Read-only queries for business partners (OCRD), cost centers (OOCR), and projects (OPRJ)

SAP data access has a toggle (`Management` table, `hana_enable` param):
- When HANA is enabled: uses `App\Helpers\Hana` (raw ODBC queries via `odbc_connect`/`odbc_exec`)
- When HANA is disabled: uses Laravel's `DB::connection('sap')` (SQL Server)

### SIAT Integration (Bolivian Tax Authority)

Invoice validation via SIAT REST API:
- **Controller:** `App\Http\Controllers\Siat\SiatController` (`HandleConsultaFactura`)
- **Route:** `POST /siat/consulta` (name: `siat.consulta`, inside `auth` middleware)
- **API endpoint:** PUT to `https://siatrest.impuestos.gob.bo/sre-sfe-shared-v2-rest/consulta/factura`
- **Request body fields:** `cuf`, `nitEmisor` (int), `numeroFactura` (int)
- **Required headers:** `Origin: https://siat.impuestos.gob.bo`, `Referer`, `Accept`
- **Response:** `{ transaccion: bool, objeto: { factura data }, mensajes: [] }`
- Frontend uses `QrcodeStream` (camera) with manual URL fallback to scan QR codes from Bolivian invoices

### Controller Naming Convention

Controller methods follow a `Handle{Action}{Entity}` pattern (e.g., `HandleIndexUser`, `HandleStoreAccountability`, `HandleEditDocument`). This is consistent across all controllers.

### Domain Modules

Routes and controllers are organized by domain under `panel/` prefix:
- **Administration** (`panel/`): Users, Profiles, Documents, Suppliers, Management (company config)
- **Accountability** (`panel/accountability/{profile_id}/manage/`): Create/edit rendiciones and their document details
- **Authorization** (`panel/accountability/authorization/`): Review and approve/reject rendiciones, export to SAP

### Key Models and Relationships

- `Profile` - expense category/type that groups accountabilities
- `Accountability` - a rendicion (expense report) belonging to a User and Profile, with status workflow
- `AccountabilityDetail` - individual expense line items within an accountability
- `Document` / `DocumentDetail` / `DocumentField` - configurable document types per profile
- `Management` - key-value configuration store (grouped by `group` field: `company`, `accountability`, `supplier`)
- `UserAuthorization` - maps users to their approvers

### Frontend Structure

- `resources/js/layouts/` - `MainLayout.vue` (authenticated, sidebar nav) and `AuthLayout.vue` (login)
- `resources/js/pages/` - mirrors backend domain structure: `accountability/`, `administration/`, `authorization/`, `auth/`
- Pages use Quasar components (`q-table`, `q-input`, `q-select`, etc.) with custom theme classes (`table-theme`, `card-form`, `input-theme`)
- Dark mode support is built into `MainLayout.vue` via `.body--dark` CSS overrides
- Detail creation pages have two variants: `CreateDetail.vue` (stepper-based) and `CreateDetailNew.vue` (single-page card layout with QR scanner)

### Configuration via Management Table

Runtime app configuration is stored in the `management` table (not .env), accessed as `Management::where('group', $group)->get()`. Groups include company info, accountability settings, and supplier field mappings.

## Language

The application UI and flash messages are in **Spanish**. Maintain Spanish for all user-facing strings.
