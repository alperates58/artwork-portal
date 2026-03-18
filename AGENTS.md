# AGENTS.md

## Project Overview
This repository is an existing PHP/Laravel-based project named `artwork-portal`.

It is intended to become a production-grade Supplier Artwork Portal for managing supplier access to purchase orders, order lines, and artwork revisions.

The current repository may contain:
- incomplete implementation
- placeholder code
- partially scaffolded Laravel structure
- inconsistent Docker/runtime setup
- missing domain models and relationships
- unfinished UI flows

Do NOT assume the project is fully working.

---

## Product Goal
The target system is a Supplier Artwork Portal with these core capabilities:

- Supplier login with role-based access
- Suppliers can only see their own authorized purchase orders / order lines
- Internal users can upload and manage artwork revisions
- Only one active/current revision per order line
- Files are stored in DigitalOcean Spaces, not local disk
- Secure file access with authorization checks
- Full audit logging for views, downloads, uploads, and user actions

Target roles:
- Admin
- Purchasing
- Graphics Department
- Supplier

---

## Main Objective
Your job is NOT to rewrite the whole project blindly.

Your job is to:
1. Preserve the existing architecture where reasonable
2. Compare the current codebase against the target product requirements
3. Identify gaps and implementation risks
4. Apply safe and minimal fixes
5. Build only the MVP scope unless explicitly asked for more

---

## MVP Scope
Focus on MVP only unless instructed otherwise:

- authentication / login
- role-based authorization
- supplier management
- purchase order listing
- purchase order line structure
- artwork upload
- artwork revision management
- active/current revision logic
- secure file download
- basic audit logging

Do NOT build Phase 2 or Phase 3 features unless explicitly requested.

---

## Critical Rules
- Do NOT blindly rewrite the project
- Do NOT change architecture unless necessary
- Prefer minimal safe changes over large refactors
- Do NOT fake completion
- File existence does NOT mean correctness
- Verify Laravel, Docker, env, database, routing, storage, and authorization properly
- If something cannot be verified, say so clearly
- Avoid creating unnecessary abstractions

---

## Technical Expectations
Preferred stack:
- Laravel
- MySQL or PostgreSQL
- Nginx
- Docker / Docker Compose
- DigitalOcean Spaces for file storage
- Secure signed or controlled file delivery
- Turkish UI
- Modern, clean, responsive admin panel

---

## Domain Expectations
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

These may not all exist yet. Detect what exists, what is missing, and what is inconsistent.

---

## Output Expectations
When asked to analyze:
- provide gap analysis against target product
- classify issues by severity
- identify structural blockers
- identify MVP blockers

When asked to implement:
- implement only approved scope
- list changed files
- explain why each file changed
- identify remaining manual steps
- identify remaining risks

Always answer clearly:
1. Is the current project structurally sound?
2. Is it runnable?
3. What is missing for MVP?
4. What was fixed?
5. What should be done next?

---

## Behavior
- First analyze
- Then plan
- Then implement
- Never jump into uncontrolled full-project generation
- Never claim production readiness without verification