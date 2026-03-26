# AGENTS.md

## Project Overview

This repository is an EXISTING Laravel 11 application named `artwork-portal`.

Project purpose:
A Supplier Artwork Portal for managing supplier access to purchase orders, order lines, artworks, and artwork revisions.

Current reality:
- This is NOT a greenfield project
- The application already runs in Docker
- Setup wizard is completed
- Application is accessible in browser
- Admin panel works
- Core MVP flows already exist

The project has evolved beyond MVP and now includes:

- Admin update/version management system
- Release manifest and changelog tracking
- Mikro ERP integration (Phase 2)
- Exchange/SMTP mail notification system
- Admin-manageable runtime settings (secret-safe)
- Asset pipeline using Vite (no Tailwind CDN dependency)
- Deployment target: DigitalOcean (Droplet + Spaces)

Do NOT treat this repository as an incomplete scaffold unless verification clearly proves otherwise.

---

## Current Stack

- Laravel 11
- PHP-FPM
- Nginx
- MySQL
- Redis
- Docker / Docker Compose
- Local development on Windows + Docker
- Storage supports:
  - local disk
  - DigitalOcean Spaces
- Vite-based asset pipeline

---

## Main Objective

Your job is NOT to rewrite the whole project.

Your job is to:
1. Preserve the existing architecture where reasonable
2. Preserve working behavior
3. Identify real gaps, regressions, bottlenecks, and risks
4. Apply safe and minimal fixes
5. Build only the requested scope

---

## Current Working Scope (MUST BE PRESERVED)

The following features already exist and must continue working:

- authentication / login
- role-based authorization
- supplier management
- supplier_users mapping
- purchase order listing
- purchase order lines
- artworks
- artwork revision management
- active/current revision logic
- secure file download
- audit logging
- revision view/download logging
- update/version tracking system
- Mikro ERP sync integration
- mail notification system
- admin settings system

Do NOT remove, redesign, or weaken these flows.

---

## Business Rules (CRITICAL - DO NOT BREAK)

### Supplier Access
- Supplier users may only see records they are authorized to access
- Access must be determined via supplier mapping rules
- Do NOT rely only on `users.supplier_id` if mapping logic exists

### Artwork Revisions
- Each order line can have multiple revisions
- Only one revision may be active/current for a line at a time
- Portal and download flows must respect active revision logic

### Upload Authorization
- Allowed roles: admin, graphic
- Forbidden roles: supplier, purchasing

### Download Authorization
Must verify:
- order access
- supplier access
- active revision rules
- permission flags

### Logging
The system must log:
- uploads
- views
- downloads
- audit trail

Do NOT remove or bypass logging.

---

## Versioning & Update System (CRITICAL)

The application includes a versioned update system.

Rules:
- APP_VERSION, CHANGELOG.md, and releases/manifest.json must ALWAYS be consistent
- Admin panel shows update history and release notes
- Do NOT introduce changes without updating version metadata when meaningful
- Do NOT break update visibility or history
- Do NOT implement unsafe auto-deploy or rollback via web

---

## Mikro ERP Integration (CRITICAL)

Rules:
- Supplier-based synchronization must be preserved
- Use `supplier_mikro_accounts` as source of truth
- `supplier_code` is the identity key (NOT supplier_name)
- Order identity must NOT be globally unique (supplier-scoped)
- `line_no` must use real ERP identity (`sip_satirno`)
- Do NOT create parallel order systems
- Prefer stable SQL VIEW contract
- Sync must be queue-based and idempotent

---

## Mail Notification System

Rules:
- Mail must be queue-based
- Must NOT block main flows
- Secrets must NEVER be exposed
- Runtime behavior is admin-managed
- Test mail and real mail must use same logic
- Prevent duplicate notifications

---

## Admin Settings System

Rules:
- Use existing system_settings structure
- Do NOT create parallel config systems
- Secrets must NOT be shown in UI
- Empty input must preserve existing values
- Must be admin-only where required

---

## Performance Priority (VERY IMPORTANT)

Performance is a PRIMARY concern.

Focus on:
- N+1 queries
- inefficient eager loading
- repeated queries in loops
- large Blade rendering cost
- Redis/session/cache config
- OPcache config
- Docker + Windows volume I/O
- APP_DEBUG overhead
- config/route/view caching
- frontend asset loading (no CDN dependency)

Do NOT:
- overengineer
- introduce heavy caching layers
- redesign architecture

Prefer:
- surgical fixes
- minimal query optimization
- environment correctness

---

## Scope Control Rules

Unless explicitly requested, do NOT:

- rewrite project
- refactor large areas
- redesign UI fully
- introduce new architectures
- replace storage system
- break compatibility

---

## Admin Panel UX

Rules:
- Settings must use sub-navigation (not long pages)
- Avoid clutter
- Keep sidebar clean
- Prefer incremental improvements
- Do NOT perform full redesign unless requested

---

## Technical Expectations

Preserve:

- Laravel
- MySQL
- Redis
- Docker
- Spaces compatibility
- Vite asset system
- Turkish UI

---

## Performance Investigation Expectations

When analyzing performance:

- Identify root cause first
- Separate:
  - app-level
  - DB-level
  - Redis-level
  - Docker-level
- Inspect:
  - Blade rendering
  - relationship usage
  - session/cache config
  - container performance

---

## Output Expectations

When responding:

1. Root cause analysis  
2. Files changed  
3. What was improved  
4. Local vs production recommendations  
5. Manual test steps  
6. Remaining risks  

Also clearly state:
- what is verified
- what is inferred
- what needs confirmation

---

## Behavior

- First analyze
- Then plan
- Then implement
- Never jump into full rewrite
- Never assume project is incomplete
- Never claim production-ready without proof
- Always preserve working flows