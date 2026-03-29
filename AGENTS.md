# AGENTS.md — Lider Portal Project Guide

> **Read this entire file before touching any code.**
> This is a mature, production-running Laravel 11 application.
> Do NOT treat it as a scaffold. Do NOT rewrite unless explicitly told to.

---

## 1. Project Overview

**Name:** Lider Portal (artwork-portal)
**Purpose:** B2B Supplier Artwork Portal — suppliers upload/approve artworks for purchase orders; internal teams manage orders, artworks, revisions, ERP sync, and reporting.
**Status:** Production-ready. Running on Docker + DigitalOcean.
**Language:** Turkish UI throughout (`tr` locale). All labels, flash messages, validation messages, and views are in Turkish.

### Key User Roles
| Role | Access |
|------|--------|
| `admin` | Full system access |
| `purchasing` | Orders, suppliers, reports, gallery (view) |
| `graphic` | Artwork upload/gallery management, dashboard |
| `supplier` | Portal only — their own orders + revisions |

---

## 2. Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Framework | Laravel | 11.x |
| PHP | PHP-FPM | 8.3 |
| Database | MySQL | 8.x |
| Cache / Queue / Session | Redis | 7.x |
| Web Server | Nginx | 1.25 |
| Container | Docker + Docker Compose | — |
| Frontend CSS | Tailwind CSS | v3.4 |
| Frontend JS | Alpine.js | v3.15 |
| Build Tool | Vite | v5.4 |
| File Storage | Local + DigitalOcean Spaces (S3) | — |
| ERP Integration | Mikro ERP (REST) | Phase 2 |
| OS (dev) | Windows 11 + Docker Desktop | — |

**No CDN dependencies.** All assets built locally via `npm run build`.

---

## 3. Repository Structure

```
artwork-portal/
├── app/
│   ├── Console/Commands/          # Artisan commands (SyncErpOrders, PruneAuditLogs, etc.)
│   ├── Enums/                     # UserRole, ArtworkStatus, ErpSyncStatus, MikroSyncConflictCode
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/             # Admin-only controllers
│   │   │   ├── Artwork/           # Artwork upload, download, gallery preview
│   │   │   ├── Auth/              # Login, password reset
│   │   │   ├── Faz2/              # Approval workflow controllers
│   │   │   ├── Order/             # Order + order line controllers
│   │   │   └── Portal/            # Supplier portal controllers
│   │   └── Middleware/            # CheckRole, EnsureUserIsActive, LogSlowRequests
│   ├── Jobs/                      # Queued jobs (mail, ERP sync)
│   ├── Models/                    # 25 Eloquent models
│   ├── Notifications/             # Laravel notifications
│   ├── Policies/                  # ArtworkPolicy, OrderPolicy
│   ├── Providers/                 # AppServiceProvider, etc.
│   ├── Services/
│   │   ├── Erp/                   # MikroErpService, MikroOrderService, PayloadNormalizer
│   │   ├── Faz2/                  # ArtworkApprovalService, MikroErpSyncService
│   │   ├── Faz3/                  # QualityDocumentService, SampleApprovalService
│   │   └── Mikro/                 # MikroClient, MikroAuth, MikroException
│   └── Support/                   # DisplayText helper
├── config/
│   ├── artwork.php                # Upload limits, allowed extensions, storage prefix
│   ├── portal.php                 # Brand name, logo, favicon paths
│   ├── mikro.php                  # Mikro ERP connection config
│   └── [standard Laravel configs]
├── database/
│   ├── migrations/                # 34 migrations (see Section 8)
│   └── seeders/
├── docker/                        # PHP-FPM, Nginx, MySQL Dockerfiles + configs
├── releases/
│   └── manifest.json              # Version manifest (MUST stay in sync with APP_VERSION)
├── resources/
│   ├── css/app.css                # Tailwind CSS entry
│   ├── js/app.js                  # Alpine.js entry
│   └── views/
│       ├── admin/                 # Admin panel views
│       ├── artworks/              # Artwork upload/revision views
│       ├── artwork-gallery/       # Gallery views
│       ├── auth/                  # Login, password reset
│       ├── components/ui/         # Reusable Blade components
│       ├── emails/                # Email templates (Turkish)
│       ├── layouts/app.blade.php  # Master layout
│       └── [portal, order, dashboard, profile, search views]
├── routes/web.php                 # All routes (single file)
├── tests/                         # PHPUnit (unit + feature)
├── AGENTS.md                      # ← This file
├── CHANGELOG.md                   # Version history
├── UPDATE.md                      # Update & deployment guide
└── releases/manifest.json         # Release metadata
```

---

## 4. Core Models & Relationships

### Critical Model Map

```
Supplier ──< SupplierUser >── User
    │
    └──< PurchaseOrder ──< PurchaseOrderLine ──── Artwork ──< ArtworkRevision
                                                    │
                                                    └── activeRevision (hasOne, is_active=true)

User ──< AuditLog
User ──< ArtworkRevision (uploaded_by)
User ──< ArtworkGallery (uploaded_by)
ArtworkRevision >── ArtworkGallery (reused from gallery)
```

### Key Models

| Model | Table | Critical Fields |
|-------|-------|----------------|
| `User` | `users` | `role` (enum), `supplier_id`, `is_active`, `permissions` (array), `department_id` |
| `Supplier` | `suppliers` | `name`, `code`, `email`, `is_active`, soft-deletes |
| `SupplierUser` | `supplier_users` | `user_id`, `supplier_id`, `is_primary`, `can_download`, `can_approve`, `title` |
| `PurchaseOrder` | `purchase_orders` | `order_no`, `supplier_id`, `status`, `erp_source`, `source_metadata` (json) |
| `PurchaseOrderLine` | `purchase_order_lines` | `product_code`, `line_no`, `artwork_status` (enum), `quantity`, `shipped_quantity` |
| `Artwork` | `artworks` | `order_line_id`, `active_revision_id` |
| `ArtworkRevision` | `artwork_revisions` | `revision_no`, `is_active`, `uploaded_by`, `approval_status`, `spaces_path` |
| `ArtworkGallery` | `artwork_gallery` | `name`, `stock_code`, `category_id`, `file_path`, `file_disk` |
| `AuditLog` | `audit_logs` | `user_id`, `action`, `model_type`, `model_id`, `payload` (json) |
| `SystemSetting` | `system_settings` | `key`, `value` — runtime config, NEVER expose secrets in UI |
| `DataTransferRecord` | `data_transfer_records` | `direction`, `entity_type`, `entity_key`, `selection_hash`, `payload_hash` |
| `SupplierMikroAccount` | `supplier_mikro_accounts` | `supplier_id`, `mikro_cari_kod`, `mikro_company_code`, `is_active` |
| `Department` | `departments` | `name`, `permissions` (array) |
| `CustomReport` | `custom_reports` | `dimensions` (array), `metrics` (array), `chart_type`, `filters` (array) |

### Enums (app/Enums/)

```php
// UserRole
UserRole::ADMIN      → 'admin'
UserRole::PURCHASING → 'purchasing'
UserRole::GRAPHIC    → 'graphic'
UserRole::SUPPLIER   → 'supplier'
// Methods: ->label(), ->isInternal()

// ArtworkStatus
ArtworkStatus::PENDING   → 'pending'
ArtworkStatus::UPLOADED  → 'uploaded'
ArtworkStatus::REVISION  → 'revision'
ArtworkStatus::APPROVED  → 'approved'
// Methods: ->label(), ->badgeClass()

// ErpSyncStatus
ErpSyncStatus::SUCCESS, FAILED, CONFLICT, PARTIAL, PENDING
```

---

## 5. Route Naming Convention

All routes follow a consistent naming pattern. **Always use named routes** — never hardcode URLs.

### Route Name Patterns
```
admin.{resource}.{action}      # Admin panel
admin.{resource}.{sub}.{action}
portal.{resource}.{action}     # Supplier portal
{resource}.{action}            # Internal (purchasing, graphic)
```

### Full Route Reference

```
# Auth
login, logout, password.request, password.email, password.reset, password.update

# Internal (role: admin, purchasing, graphic)
dashboard
orders.index / .create / .store / .show / .edit / .update / .destroy
orders.notes.store
order-lines.show
artworks.create / .store / .show / .activate / .destroy / .revisions
artwork.download
artworks.gallery.preview

# Portal (role: supplier)
portal.orders.index / .show
portal.download
approval.seen / .approve

# Profile
profile.edit / .update / .password / .photo / .photo.delete

# Admin — Users
admin.users.index / .create / .store / .edit / .update / .toggle / .destroy

# Admin — Suppliers
admin.suppliers.index / .create / .store / .show / .edit / .update / .destroy
admin.suppliers.sync

# Admin — Permissions
admin.permissions.index / .show / .update / .reset

# Admin — Departments
admin.departments.index / .create / .store / .edit / .update / .destroy

# Admin — Settings
admin.settings.edit / .update
admin.settings.mail-connection-test / .mail-test
admin.settings.update-check / .update-prepare / .deploy / .apply-only / .commits
admin.integrations.mikro.test

# Admin — Reports
admin.reports.index / .lead-time / .pending / .category / .stock-code / .timeline
admin.reports.factory.index / .create / .store / .show / .update / .destroy / .preview

# Admin — Logs
admin.logs.index
admin.logs.timeline          # AJAX endpoint — returns JSON

# Admin — Gallery
admin.artwork-gallery.index / .manage / .edit / .update / .destroy
admin.artwork-gallery.categories.store / .destroy
admin.artwork-gallery.tags.store / .destroy

# Admin — Data Transfer
admin.data-transfer.index / .export / .import / .destroy-imported

# Admin — ERP
admin.erp.sync

# Misc
search
notifications.index / .read
```

---

## 6. Permission & Authorization System

### Role-Based Access (Middleware)
```php
Route::middleware('role:admin')                    // admin only
Route::middleware('role:admin,purchasing')         // admin OR purchasing
Route::middleware('role:admin,purchasing,graphic') // all internal roles
Route::middleware('role:supplier')                 // supplier portal
```

### Fine-Grained Permissions
Stored in `users.permissions` (JSON array). Applied on top of role defaults.

```php
// Check in controllers:
abort_if(! auth()->user()->isAdmin() && ! auth()->user()->hasPermission('logs', 'view'), 403);

// Permission structure: ['screen' => ['view', 'create', 'edit', 'delete']]
// Screens: orders, artworks, suppliers, users, reports, logs, settings, gallery, erp
```

### Supplier Access (CRITICAL — DO NOT BYPASS)
```php
// WRONG — never do this:
$orders = PurchaseOrder::where('supplier_id', $user->supplier_id)->get();

// CORRECT — use supplier mapping via pivot:
$supplierIds = $user->accessibleSupplierIds(); // uses supplier_users pivot
$orders = PurchaseOrder::whereIn('supplier_id', $supplierIds)->get();

// Download permission:
$user->canDownloadForSupplier($supplierId); // checks supplier_users.can_download
```

---

## 7. Frontend Conventions

### Tailwind CSS
- Version: **v3.4** (not v4 — do NOT upgrade)
- Custom colors: `brand-*` (defined in `tailwind.config.js`)
- Card component: `class="card"` (custom Tailwind component)
- Input: `class="input"`, Label: `class="label"`, Buttons: `class="btn btn-primary"` / `btn-secondary`
- Never add inline styles for colors — use Tailwind classes

### Alpine.js
- Version: **v3.15** (loaded from `resources/js/app.js`)
- No CDN. No Alpine plugins (x-collapse, x-mask, etc. are NOT installed).
- Pattern for page components:
  ```blade
  <div x-data="pageFunction()">
      ...
  </div>
  @push('scripts')
  <script>
  function pageFunction() {
      return {
          property: value,
          method() { ... },
      };
  }
  </script>
  @endpush
  ```
- Use `x-cloak` for elements that should be hidden before Alpine initializes (CSS: `[x-cloak]{display:none!important}` is in app.css)
- For AJAX: use native `fetch()` with `X-Requested-With: XMLHttpRequest` header
- Use `@input.debounce.400ms` for live search inputs

### Blade Layout
```blade
@extends('layouts.app')
@section('title', 'Page Title')
@section('page-title', 'Page Title')
@section('page-subtitle', 'Subtitle text')

@section('content')
    {{-- page content --}}
@endsection

@push('scripts')
<script>
    // Alpine components or page-specific JS
</script>
@endpush
```

### UI Component Patterns
- Cards: `<div class="card p-4">` or `<div class="card overflow-x-auto">`
- Tables: `<table class="w-full text-sm">` inside `.card.overflow-x-auto`
- Badges: `<span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[11px] font-semibold bg-{color}-100 text-{color}-700">`
- Avatar initials: `strtoupper(mb_substr($user->name, 0, 2))`
- Empty states: centered, muted icon + text
- Flash messages: `session('success')` / `session('error')` displayed in layout

---

## 8. Database Conventions

### Migration Naming
```
YYYY_MM_DD_HHMMSS_description_table.php
```

### Key Constraints
- `purchase_orders`: composite unique on `(supplier_id, order_no)` — order numbers are NOT globally unique
- `artwork_revisions`: only one `is_active = true` per `artwork_id`
- `supplier_users`: unique on `(user_id, supplier_id)`
- `audit_logs`: no foreign key constraint on `user_id` (user may be deleted)
- `data_transfer_records`: same direction + entity + selection hash + payload hash combination is unique

### Soft Deletes
Tables with soft deletes: `suppliers` (use `whereNull('deleted_at')` or `Supplier::query()` which respects SoftDeletes)

### JSON Columns
- `users.permissions` — array
- `departments.permissions` — array
- `purchase_orders.shipment_payload` — json
- `purchase_orders.source_metadata` — json
- `custom_reports.dimensions`, `.metrics`, `.filters` — arrays
- `audit_logs.payload` — json (search with `LIKE '%"key":"value"%'` pattern)
- `system_settings.value` — json-encoded value

### Querying Audit Log Payload (JSON stored as text)
```php
// Search by order_no in payload:
AuditLog::where('payload', 'like', '%"order_no":"' . addslashes($value) . '"%')

// DO NOT use MySQL JSON functions (JSON_EXTRACT) — payload column is TEXT/JSON text
```

---

## 9. Audit Logging System

### AuditLogController Constants (use these everywhere)

```php
// Categories
AuditLogController::CATEGORIES = [
    'session'  => ['user.login', 'user.logout'],
    'artwork'  => ['artwork.upload', 'artwork.view', 'artwork.viewed', 'artwork.download',
                   'artwork.delete', 'artwork.approved', 'artwork.rejected', 'artwork.revision.activate'],
    'gallery'  => ['artwork.gallery.create', 'artwork.gallery.reuse', 'artwork.gallery.update', 'artwork.gallery.delete'],
    'order'    => ['order.view', 'order.create', 'order.update', 'order.delete',
                   'order_line.view', 'portal.order.view'],
    'mail'     => ['mail.notification.sent', 'mail.notification.failed', 'mail.notification.skipped',
                   'mail.notification.queued', 'mail.notification.queue_failed',
                   'mail.notification.test.sent', 'mail.notification.test.queued'],
    'erp'      => ['erp.sync', 'mikro.test.success', 'mikro.test.failed'],
]
```

### Logging Actions
All significant user actions MUST be logged via `AuditLogService`. Never bypass logging.
```php
app(AuditLogService::class)->log($action, $model, $payload);
```

---

## 10. Artwork Revision Rules (CRITICAL)

1. Each `PurchaseOrderLine` has one `Artwork` record (hasOne)
2. Each `Artwork` has many `ArtworkRevision` records
3. Only ONE revision can have `is_active = true` per `Artwork` at a time
4. `artworks.active_revision_id` must stay in sync with the active revision
5. When activating a revision: set all others to `is_active = false`, then set target to `true`, update `artworks.active_revision_id`
6. Portal and download flows MUST use `artwork->activeRevision()` — never assume latest = active
7. Upload is only allowed for: `admin`, `graphic`
8. Approval flow (`Faz2`): supplier confirms seen → supplier approves → status propagates back to `purchase_order_lines.artwork_status`

---

## 11. File Storage & Download

### Storage Disks
- `local` — temporary uploads, non-sensitive files
- `spaces` — DigitalOcean Spaces (S3-compatible) for artwork files
  - Env: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_ENDPOINT`, `AWS_BUCKET`

### Secure Download Flow
```
User requests download → DownloadController checks:
  1. auth()->check()
  2. User can access this supplier's order (via supplier_users pivot)
  3. Revision is the active revision
  4. For supplier: can_download flag on pivot

→ Generate presigned URL (TTL from config/artwork.php: ARTWORK_DOWNLOAD_TTL minutes)
→ Log download in audit_logs
→ Redirect to presigned URL
```

**NEVER** expose direct file paths or Spaces URLs without presigned generation.

---

## 12. Mikro ERP Integration (CRITICAL)

### Architecture
- Service: `app/Services/Erp/MikroErpService.php`
- Client: `app/Services/Mikro/MikroClient.php`
- Supplier accounts: `supplier_mikro_accounts` table (one per supplier)

### Rules
- **Identity key**: `mikro_cari_kod` (NOT supplier name, NOT email)
- **Order identity**: composite `(supplier_id, order_no)` — NOT globally unique
- **Line identity**: `sip_satirno` from ERP = `purchase_order_lines.line_no`
- Sync is **queue-based** and **idempotent** — running twice must not duplicate data
- Use `SyncSupplierOrdersJob` for per-supplier sync
- Use `SyncAllActiveSuppliersJob` for bulk sync
- Conflict handling via `MikroSyncConflictCode` enum

### DO NOT
- Create parallel order import systems
- Change identity keys
- Make sync synchronous (blocking)
- Remove conflict tracking

---

## 13. Mail Notification System

### Architecture
- Dispatcher: `MailNotificationDispatcher.php`
- Service: `MailNotificationService.php`
- Jobs: `SendNewOrderNotificationJob`, `SendMailNotificationTestJob`
- Config: `system_settings` table (runtime-overridable by admin)

### Rules
- All mail is **queue-based** — dispatch to Redis queue, never block HTTP request
- Admin can configure mail settings at runtime via Settings page
- Test mail and real mail use the **same** underlying logic
- Log all sends/failures in `audit_logs` with actions: `mail.notification.*`
- NEVER expose SMTP passwords or API keys in any response/view
- Duplicate prevention: check before queuing

---

## 14. Settings System

### Runtime Settings (`system_settings` table)
```php
// Read:
PortalSettings::get('mail_host');

// Write (admin only):
PortalSettings::set('mail_host', 'smtp.example.com');
```

Key setting groups: `mail_*`, `brand_*`, `erp_*`

### Rules
- NEVER create parallel config systems (no extra `.env` keys for things managed in `system_settings`)
- Empty input on settings form = preserve existing value (don't overwrite with empty string)
- Settings page is admin-only
- Secrets (passwords, API keys) are stored but never re-displayed in forms (use placeholder `••••••••`)

---

## 15. Update & Versioning System

### Version Sources (ALL THREE must stay in sync)
1. `.env`: `APP_VERSION=x.y.z`
2. `CHANGELOG.md`: add entry for new version
3. `releases/manifest.json`: add release object

### Admin Panel
- Shows current version, git branch, last commit
- Lists recent commits (fetched from GitHub API via `GithubUpdateChecker`)
- Allows admin to trigger `git pull` + migrations + asset rebuild via `PortalDeployService`
- Update history stored in `portal_update_events`

### Rules
- Never change version in only one place
- Never implement unsafe auto-deploy (no arbitrary shell exec from web)
- `PortalDeployService` is the ONLY safe deploy entry point
- Do NOT break update visibility or history

---

## 16. Dashboard & Caching

### DashboardCacheService
- Caches: pending artwork counts, recent revisions, supplier stats
- Cache driver: Redis
- TTL: configured per metric (typically 5–15 minutes)
- Cache is invalidated on relevant model mutations

### DO NOT
- Add expensive queries to DashboardController without caching
- Bypass `DashboardCacheService` for dashboard metrics

---

## 17. Report Factory (Custom Reports)

### Architecture
- Model: `CustomReport` (dimensions, metrics, filters, chart_type stored as arrays)
- Builder: `resources/views/admin/reports/factory/builder.blade.php`
- Preview: `ReportFactoryController@preview` (returns JSON)
- Query: `ReportQueryService`

### Frontend (Alpine.js + Chart.js)
- Chart.js v4.4.0 loaded locally (not CDN)
- Chart renders AFTER `$nextTick()` — **critical ordering**: set state → await `$nextTick()` → render chart
- Mobile: tap-to-toggle field selection (drag-and-drop doesn't work on touch)

---

## 18. Audit Log Timeline (AJAX)

### Endpoint
`GET /admin/loglar/zaman-cizelgesi` → `AuditLogController@timeline`

### Search Types
- `order_no` — direct payload LIKE search
- `stock_code` — resolve via `PurchaseOrderLine` join to `PurchaseOrder`
- `supplier_id` — resolve via `PurchaseOrder` where supplier_id

### Response Format
```json
{
  "logs": [
    {
      "id": 1,
      "action": "order.create",
      "action_label": "Sipariş oluşturuldu",
      "color": "amber",
      "category": "order",
      "user_name": "Ali Veli",
      "user_role": "Satın Alma",
      "user_initials": "AV",
      "ip": "192.168.1.1",
      "details": [{"key": "Sipariş", "value": "SIP-001"}],
      "date": "29.03.2026",
      "time": "14:22:11",
      "datetime": "2026-03-29T14:22:11+00:00",
      "day_group": "29.03.2026"
    }
  ],
  "count": 1,
  "meta": {"type": "Sipariş No", "value": "SIP-001"}
}
```

---

## 19. Performance Rules

### Always
- Eager load relationships: `->with(['user:id,name,role', 'supplier:id,name'])` — select only needed columns
- Use `simplePaginate(50)` for large tables (faster than `paginate()`)
- Use `->select([...])` to avoid `SELECT *`
- Cache dashboard metrics via `DashboardCacheService`
- Use `->pluck()` when only one column is needed
- Use `->value()` when only one scalar is needed

### Never
- Run queries inside Blade loops
- Load full models when only IDs or names are needed
- Add synchronous HTTP calls in HTTP request lifecycle (use Jobs)
- Use `APP_DEBUG=true` in production (massive overhead)

### N+1 Prevention
```php
// Bad:
$orders->each(fn($o) => $o->supplier->name); // N+1

// Good:
Order::with('supplier:id,name')->get();
```

---

## 20. Code Style & Conventions

### PHP
- PHP 8.3 features: enums, readonly properties, named arguments, match expressions, nullsafe operator
- Return types on all controller methods
- Use `abort_if()` for authorization guards
- Use `$request->validated()` after FormRequest validation
- Service classes for business logic — keep controllers thin
- Use `Carbon` for all date manipulation

### Turkish Characters
All UI text must use correct Turkish characters:
```
Correct: ğ ü ş ı ö ç Ğ Ü Ş İ Ö Ç
Wrong: g u s i o c (ASCII substitutes)
```
Check: Sipariş (not Siparis), Tedarikçi (not Tedarikci), Güncelle (not Guncelle), İşlem (not Islem)

### Blade
- Use `{{ }}` for escaped output (always, unless explicitly safe)
- Use `{!! !!}` ONLY for trusted HTML (e.g., pre-escaped badge HTML)
- Use `@php` blocks for view-local PHP logic
- Use `compact()` in controllers to pass variables to views
- Use `AuditLogController::CONSTANT` to access constants from views (already imported with `@php use ...`)

### Routes
- Turkish URL slugs: `/siparisler`, `/tedarikciler`, `/loglar`, `/raporlar`, `/ayarlar`
- Always name routes
- Always group by middleware

---

## 21. Docker Setup

### Services
```yaml
app         # PHP-FPM 8.3 — container: artwork_app
nginx       # Nginx 1.25 — ports: 80:80, 443:443 — container: artwork_nginx
mysql       # MySQL 8 — port: 3306 — container: artwork_mysql
redis       # Redis 7 — container: artwork_redis
node        # Node 24 (build only) — container: artwork_node
```

### Common Commands
```bash
# Start
docker compose up -d

# Shell into app
docker compose exec app bash

# Run migrations
docker compose exec app php artisan migrate

# Build frontend
docker compose run --rm node npm run build

# Clear cache
docker compose exec app php artisan optimize:clear

# View logs
docker compose logs -f app
```

### Windows Development Notes
- Volume I/O is slower on Windows with bind mounts — this is expected
- Use `APP_DEBUG=false` if testing performance (debug adds significant overhead)
- OPcache is enabled in PHP-FPM config (`docker/php/`)

---

## 22. Testing

### Running Tests
```bash
docker compose exec app php artisan test
docker compose exec app php artisan test --filter=Unit
docker compose exec app php artisan test --filter=Feature
```

### Test Database
- Uses `.env.testing` if present, or `DB_DATABASE_TEST`
- Runs migrations fresh for each test class with `RefreshDatabase` trait

### What to Test
When adding new features:
1. Write a Feature test that hits the HTTP endpoint
2. Test authorization (assert 403 for unauthorized roles)
3. Test the happy path
4. Test validation failures

---

## 23. Critical Rules Summary (DO NOT VIOLATE)

### NEVER do these:
1. **Bypass supplier access control** — always use `accessibleSupplierIds()`, never raw `users.supplier_id`
2. **Expose secrets in UI** — `system_settings` passwords/keys never displayed; use `••••••••` placeholder
3. **Break active revision logic** — always use `artwork->activeRevision()`, not latest by ID
4. **Remove audit logging** — every upload, download, view, approval must be logged
5. **Make ERP sync synchronous** — always dispatch to queue
6. **Change ERP identity keys** — `mikro_cari_kod` and composite `(supplier_id, order_no)` are immutable
7. **Deploy unsafely** — use only `PortalDeployService`; no arbitrary shell exec
8. **Desync versions** — `.env APP_VERSION`, `CHANGELOG.md`, `releases/manifest.json` must match
9. **Duplicate presigned URL generation** — always go through `DownloadController`
10. **Allow supplier role to upload** — upload only for `admin` and `graphic`
11. **Use CDN for assets** — Vite builds everything locally
12. **Use Alpine plugins not installed** — only core Alpine.js v3 (no x-collapse, x-mask, etc.)
13. **Write ASCII for Turkish text** — always use ğüşıöç/ĞÜŞİÖÇ in all UI strings
14. **Create parallel config systems** — use `system_settings` + `PortalSettings` service
15. **Use `SELECT *` on large tables** — always `->select([...])` with specific columns

### ALWAYS do these:
1. Read relevant existing code before modifying
2. Eager load relationships (`->with()`) to prevent N+1
3. Use named routes (`route('admin.logs.index')`)
4. Use `abort_if()` for authorization in controllers
5. Dispatch mail and ERP sync to queue
6. Log meaningful actions via `AuditLogService`
7. Use `simplePaginate()` for table listings
8. Keep Blade views in Turkish
9. Use `PortalSettings::get()` for runtime settings
10. Follow existing code patterns in the file you're editing

---

## 24. Adding New Features — Checklist

Before writing any code, answer:
- [ ] Does a similar pattern already exist? (Find it and follow it)
- [ ] What routes does this need? (Follow naming convention)
- [ ] What middleware applies? (role, permission, or both)
- [ ] Does it need audit logging? (Almost certainly yes)
- [ ] Does it touch supplier data? (Add access control check)
- [ ] Does it send mail? (Dispatch to queue, never sync)
- [ ] Does it affect dashboard metrics? (Invalidate DashboardCache)
- [ ] Does it add a new table? (Write migration, update this doc)
- [ ] Does it need a permission? (Add to `hasPermission()` check)

---

## 25. Known Architecture Decisions

| Decision | Rationale |
|----------|-----------|
| Single `routes/web.php` | Simplicity; app has no API consumers |
| `supplier_users` pivot (not just FK) | Multi-supplier users; per-supplier download/approve flags |
| Audit log payload as JSON text | Flexibility; searched with LIKE patterns |
| Queue for all mail | Non-blocking; mail server outages don't affect UX |
| `DashboardCacheService` | Dashboard queries are expensive; 5-min cache acceptable |
| Faz2/Faz3 namespacing | Phased approval/quality feature rollout |
| Turkish URL slugs | Full Turkish product; URLs are user-visible |
| `releases/manifest.json` | Enables in-app update system without external API dependency |
| `system_settings` for mail config | Allows runtime reconfiguration without `.env` access |

---

*Last updated: 2026-03-29*
*Version: 1.9.x*
