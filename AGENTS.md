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
- Storage supports both:
  - local disk
  - DigitalOcean Spaces

---

## Main Objective

Your job is NOT to rewrite the whole project.

Your job is to:
1. Preserve the existing architecture where reasonable
2. Preserve working MVP behavior
3. Identify real gaps, regressions, bottlenecks, and risks
4. Apply safe and minimal fixes
5. Build only the requested scope

---

## Current Working MVP Scope (MUST BE PRESERVED)

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
Authorized download must verify all required conditions, including:
- order access
- supplier access
- active/current revision restrictions where applicable
- permission flags such as download capability

### Logging
The system must continue logging:
- uploads
- views
- downloads
- audit trail actions

Do NOT silently remove or bypass logging behavior.

---

## Performance Priority (VERY IMPORTANT)

The application is currently experiencing noticeable slowness in browser UI, especially in admin pages.

Performance is now a PRIMARY priority.

Focus on:
- root cause analysis of slowness
- N+1 query detection and removal
- repeated queries in loops
- inefficient eager loading
- unnecessary data loading
- inefficient list/detail queries
- Redis/session/cache misconfiguration
- Laravel runtime optimization
- logging overhead when relevant
- Docker local environment misconfiguration
- Windows volume mount performance impact
- APP_DEBUG and local dev overhead
- config/route/view cache strategy
- PHP OPcache configuration
- queue/session/cache driver correctness

Do NOT:
- add heavy abstractions
- introduce large caching systems
- redesign architecture
- overengineer performance fixes

Prefer:
- surgical query fixes
- careful eager loading
- minimal index additions only if clearly justified
- config cleanup
- environment correctness
- simple, explainable improvements

---

## Scope Control Rules

Unless explicitly requested, do NOT:
- rewrite the project
- refactor large parts only for cleanliness
- introduce Phase 2 / Phase 3 features
- redesign the UI
- replace the storage architecture
- replace the authorization model
- move major logic into new layers without clear need

Prefer minimal safe changes over large refactors.

---

## Technical Expectations

Use and preserve the current stack:

- Laravel
- MySQL
- Nginx
- Docker / Docker Compose
- Redis
- local + Spaces-compatible file storage
- controlled/authorized file delivery
- Turkish UI where already present

Keep local and production compatibility in mind.

---

## Domain Model Expectations

Expected main entities include:
- users
- roles
- permissions
- suppliers
- supplier_users
- purchase_orders
- purchase_order_lines
- artworks
- artwork_revisions
- artwork_download_logs
- artwork_view_logs
- audit_logs

These already exist at least partially or fully.
Do not assume absence without verification.

---

## Critical Rules

- Do NOT blindly rewrite the project
- Do NOT change architecture unless truly necessary
- Do NOT fake completion
- File existence does NOT guarantee correctness
- Verify behavior through code paths, config, queries, and tests where possible
- If something cannot be verified, state that clearly
- Preserve backward compatibility
- Avoid unnecessary abstractions
- Prefer small, surgical changes
- Explain why each change is made

---

## Performance Investigation Expectations

When working on performance:
- identify likely root causes first
- distinguish app-level, DB-level, Redis-level, and Docker/environment-level slowness
- inspect admin pages, portal pages, list pages, and detail pages
- check for Blade-triggered lazy loading
- check for repeated relationship access
- verify session/cache/queue/lock configuration
- verify Redis is actually usable if configured
- verify local Docker setup is not causing avoidable slowdown

If a change is production-only, say so explicitly.
If a change is unsafe for local development, say so explicitly.

---

## Output Expectations

When asked to analyze or optimize, provide:

1. Root cause analysis  
2. Files changed  
3. What was improved  
4. Local vs production config recommendations  
5. Manual test steps  
6. Remaining risks  

Also make clear:
- what was verified
- what was inferred
- what still needs manual confirmation

---

## Behavior

- First analyze
- Then plan
- Then implement
- Never jump into uncontrolled full-project generation
- Never claim production readiness without verification
- Never treat the project as empty or unfinished by default
- Always preserve working flows unless they are clearly broken