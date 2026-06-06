# Doctorato Polyclinic — AI Assistant Guide

Context for Claude / AI agents working in this repo. Read before
suggesting changes.

---

## Stack

- **Laravel 12** (PHP 8.3+) + **Vue 3** + **Inertia.js v2**
- **Tailwind CSS 4** (via Vite)
- **MySQL 8+** in prod, **SQLite** in CI/testing
- **Agora** for video (lazy-loaded in `Components/Telemedicine/VideoRoom.vue`)
- **Paymob** or **Stripe** for payments
- **spatie/laravel-backup** for DB/file backups
- Runs on **shared cPanel hosting** — Laravel root == document root
  (`public_html/`), no separate `public/` docroot

---

## Panels (user-facing areas)

Each has its own layout + middleware + route file:

| Panel      | Prefix                   | Layout                      | Middleware         |
|------------|--------------------------|-----------------------------|--------------------|
| Frontend   | `/`                      | (public)                    | `web`              |
| Patient    | `{locale}/patient/…`     | `PatientLayout.vue`         | `patient.auth`     |
| Doctor     | `/doctor/…`              | `DoctorLayout.vue`          | `doctor.auth`      |
| Secretary  | `/secretary/…`           | `SecretaryLayout.vue`       | `secretary.auth`   |
| Admin      | `/admin/…`               | `AdminLayout.vue`           | `admin.auth`       |
| Webmaster  | `/webmaster/…`           | (similar)                   | `webmaster.auth`   |

`{locale}` must be `ar` or `en` (enforced by route constraint).
Patient routes are the only ones under a `{locale}` prefix — see the
"Gotchas" section for why this matters.

---

## Key systems

- **ModuleManager** (`app/Services/ModuleManager.php`) — feature flags per
  clinic module. Slugs: `derma`, `dental`, `pediatric`, `obgyn`,
  `psychiatry`, `neurology`, `telemedicine`.
  `MEDICAL_MODULES = ['derma','dental','pediatric','obgyn','psychiatry','neurology']`
  — only these have doctors/visits/bookings. Enable with `ModuleManager::enable('slug')`.
  OB/GYN module design + build notes: `docs/OBGYN_MODULE_PLAN.md`.
  Psychiatry & Neurology (built over a shared `App\Services\NeuroPsych` layer +
  `Components/Clinical/*`): design `docs/NEUROPSYCH_MODULE_PLAN.md`, build
  checklist `docs/NEUROPSYCH_BUILD_CHECKLIST.md`. Tests under
  `tests/Feature/Neuropsych/`. Psychotherapy notes + risk assessments are gated
  by a separate `{module}.view_sensitive` permission (heightened RBAC + audit).

- **Settings** (`app/Models/Setting.php`) — key/value table, used for
  everything runtime-configurable (clinic name, payment keys, Agora
  credentials, fees, …). Encrypted keys are marked via
  `Setting::isEncryptedKey()`.

- **Module sidebar filtering** — Layout sidebars filter sections via a
  `moduleKey` field matching `modules.value[key]?.enabled === true`.

- **Week convention** — `day_of_week`: `0=Saturday, 1=Sunday, …, 6=Friday`.
  Different from Carbon's `dayOfWeek` (0=Sun). Use the map in
  `OnlineSlotGeneratorService::carbonToSystemDay()` to translate.

- **Multi-branch** (built, behind a kill-switch — see `docs/MULTI_BRANCH_ADR.md`
  + `_CHECKLIST.md`) — `Branch` model + `branches` table (Main Branch id=1).
  - **Two traits** (`App\Models\Concerns\…`): `BelongsToBranch` = global scope
    (filter) **+** stamp-on-create; `StampsBranch` = stamp/attribution only, **no
    filter** (used by the org-wide notification hub + central CRM `leads`).
  - **Kill-switch:** `config('branches.enabled')` (env `BRANCHES_ENABLED`, default
    **false**). While off the global scope is a no-op (single-clinic behaviour);
    `branch_id` is still stamped so data is ready. Always stamps a CONCRETE branch
    (`currentId() ?? default`) — never branchless.
  - **`BranchContext`** resolves the active branch: explicit `set()` → session →
    default. **Console (cron, `queue:work`, CLI) defaults to ALL-BRANCHES** so jobs
    process every branch (never silently scope to branch 1). HTTP resolves a
    concrete branch (`branch.context` middleware / default). Cron paths that CREATE
    from a parent wrap work in `runForBranch($parent->branch_id, …)` so children
    inherit the parent's branch (e.g. `CreateDailyVisits`, `DentalFollowupService`).
  - **Shared, NOT scoped:** patients (+ their records), services/prices, suppliers,
    notification channels/credentials, CRM pipeline, per-patient clinical state
    (allergies, obgyn_profiles, odontogram), templates/catalogs.
  - **Scoped:** bookings, finance, visits, inventory, doctor-schedules, HR, and all
    clinical EVENT tables (dental/derma/cosmetic/pediatric/obgyn), expenses,
    insurance, recall/satisfaction.
  - **Numbering** is branch-aware via `BranchNumber::prefix()` — main branch keeps
    the legacy `XX-YYYYMM-####`; other branches get `XX-CODE-YYYYMM-####` (no
    collisions, no index surgery). `file_number` stays global.
  - **Settings** support per-branch overrides: `settings.branch_id` (0 = global),
    `Setting::get()` prefers the branch override then falls back to global;
    `setForBranch()/clearBranchOverride()`. Gated by the kill-switch.
  - **Staff/doctor↔branch** via `branch_user`/`branch_doctor` (super_admin sees
    all). Switcher in admin/doctor/secretary headers → per-panel
    `/{panel}/switch-branch`. Assign members from `/admin/branches` (Members modal).
  - **Reports:** existing reports auto-scope; `BranchReportService` +
    `/admin/reports/branch-comparison` give the cross-branch view. `data:integrity-check`
    flags `branch_id_missing` / `branch_id_orphan`.
  - **To add a domain:** migration (nullable `branch_id` + backfill→1 + composite
    index, idempotent) → add `BelongsToBranch` to the query-root models → isolation
    test (`tests/Feature/Branch/`). Do NOT use perl to insert the trait (the import
    must be top-of-file; use a standalone `use BelongsToBranch;` line in the class).
  - **To activate:** assign staff/doctors to branches, set per-branch settings,
    create the branches, run `data:integrity-check`, then set `BRANCHES_ENABLED=true`
    on staging and verify end-to-end before production.

- **Telemedicine readiness** — `/health` and the admin Telemedicine
  settings page both compute the same 5-blocker readiness list:
  `module_disabled`, `agora_missing`, `no_payment_gateway`,
  `no_online_doctors`, `no_bookable_schedules`.

---

## Development commands

```bash
# Install
composer install && npm ci

# Dev server (Laravel + Vite HMR + queue + logs via serve.sh)
./serve.sh        # or: composer dev  → artisan serve + vite + pail + queue

# Build for prod (manifest.json is committed — see CI)
npm run build                # or: composer fresh-build

# Tests (includes Feature/Patient + Feature/Security + Feature/Admin)
composer test
composer test:telemedicine    # just OnlineConsultation tests
composer lint                 # pint --test
composer check                # lint + test (what CI runs)

# Ops / diagnostics
composer health              # prints /health JSON for local env
php artisan schedule:list    # all scheduled tasks

# Deploy (remote server equivalent runs via deploy.yml)
composer deploy              # git pull + composer install --no-dev + migrate + optimize
```

## Operational commands (production)

| Command                                  | When |
|------------------------------------------|------|
| `php artisan health:alert`               | Auto every 15 min. Emails admin on subsystem failure. |
| `php artisan data:integrity-check --alert` | Auto Mondays 05:00. Scans for orphans/drift/stuck rows. |
| `php artisan telemedicine:cleanup-stuck` | Auto hourly :10. Frees slots abandoned at checkout. |
| `php artisan patients:link-users`        | On-demand. Creates portal User accounts for Patient rows missing a `user_id`. Patient must "Forgot password" to claim. |
| `php artisan media:migrate-phi [--dry-run] [--keep-public]` | Auto on deploy + on-demand. Moves sensitive PHI files (x-rays, photos, documents, consents, insurance cards, patient photos, chat) from the public disk to the private disk. Idempotent; serving has a public-disk fallback so it never breaks display. (S1) |
| `php artisan pricing:audit [--json]`     | On-demand, READ-ONLY. Prints the resolved consultation pricing for every medical module. Run before/after any pricing change and diff. (ADR-001) |
| `php artisan pricing:backfill-module-settings [--dry-run]` | On-demand. Copies legacy `Setting` fees into `module_settings` (idempotent upsert). Read path is unchanged; verify with `pricing:audit --json` before/after. (ADR-001 Phase 1) |
| `php artisan db:seed --class=Database\\Seeders\\SpecialtyDoctorDemoSeeder` | On-demand, demo/staging only. Creates one demo doctor login per medical specialty (`demo.<module>@doctorato.net`, password `DEMO_PASSWORD`) — each owning a complete self-contained data set: patients, bookings (past + upcoming), completed visits with commissions, invoices + payments, and rich specialty clinical records (dental charts/plans/treatments/xrays, derma plans/sessions, pediatric growth/vaccinations, obgyn pregnancies/labs, neuropsych encounters/scales/meds/risk). Idempotent (skips a module whose demo doctor already has visits). NOT in the auto-deploy path — `deploy.yml` never runs `db:seed`. |

**Pricing source (ADR-001):** `module_settings` is now the primary store for all
medical-module consultation fees, with a fallback to the legacy global `Setting`
keys when a value is absent or ≤ 0 (so no zero-pricing risk). Editors dual-write
(legacy + mirrored). See `docs/ADR-001-unify-pricing-source.md`. The
`module_pricing_unset` finding in `data:integrity-check` flags any enabled module
that resolves to a 0 fee.

Human-facing admin pages:

- `/admin/diagnostics` — System/Telemedicine/Scheduler cards + log tail + Export JSON button
- `/admin/settings/telemedicine` — Readiness banner + per-blocker diagnostics
- `/admin/reports/outcomes` — cross-specialty clinical-quality / outcomes dashboard (+ CSV export)
- `/admin/doctors/{id}/service-rates` — per-doctor per-service commission rates (+ bulk apply)
- `/secretary/{psychiatry,neurology,derma,telemedicine}/overview` — front-desk (admin-only, no clinical data)
- `GET /health` — JSON for uptime monitors (200/503)

### Owner / server pending actions (not code — require server or business input)
- **`APP_ENV=production` + `APP_DEBUG=false`** in `.env` (security; turns off debug traces).
- **Payment gateway keys** (Paymob/Stripe) via `/admin/settings/telemedicine` — unblocks
  telemedicine revenue and makes `/health` green.
- **Confirm pediatric fee:** run `pricing:audit`; if `pediatric` is 0, set
  `pediatric_consultation_fee` in `/admin/pediatric/settings`.
- **ADR-001 Phase 4** (retire legacy fee keys): only after a clean soak + go-ahead.

---

## Gotchas (hard-won lessons)

1. **Laravel 12 controller param order under `{locale}`:**
   Patient controllers must read route params by **name**:
   ```php
   $docId = $request->route('docId');
   ```
   Do **not** rely on positional scalar params — Laravel 12 fills them
   against *all* matched route params, so `{locale:"ar", docId:"5"}` would
   give the controller `$docId = "ar"`. Caused a multi-hour 404 debug
   loop. (See `OnlineConsultationController::showDoctor`.)

2. **Inertia page components must be in `public/build/manifest.json`:**
   If you add a new Vue page, run `npm run build` and commit. CI checks
   for critical pages in the manifest — pages missing from the manifest
   silently 404 on production.

3. **OPcache on shared cPanel hosting:**
   Edits to controllers don't take effect until OPcache invalidates.
   If `php artisan route:cache` and `optimize:clear` don't help, drop an
   `opcache-reset.php` at the webroot, hit it once, delete it
   (already in `.gitignore`).

4. **Route-model binding edge cases:**
   Avoid naming an implicit route param after a model you don't want
   bound (`{doctor}` → `Doctor`). Prefer `{docId}` + manual lookup to
   sidestep Laravel's implicit binding entirely.

5. **Cross-panel auth:**
   `AdminAuth` uses a whitelist (`['admin','super_admin']`) and
   redirects other roles to their own panel's home rather than
   logging them out. Don't revert to blacklist — it leaks patient
   sessions into admin.

6. **`public/build/` IS committed.**
   Because the shared host has no `npm`. CI rebuilds + the manifest
   integrity check is what keeps prod honest. Don't `.gitignore` it.

7. **Arabic day names in UI:**
   Doctor profile / schedule UIs historically used Sun-first (Carbon
   default). Corrected to Saturday-first to match the backend. If you
   add a new day picker, follow the 0=Sat, 6=Fri convention.

8. **Patient SoftDeletes:**
   `Booking`, `Invoice`, `Patient`, `Lead`, `Doctor` all use SoftDeletes.
   `find()` and `findOrFail()` exclude trashed rows by default.

9. **File number auto-generation:**
   `Patient::booted()` hook auto-generates `file_number` on create. Don't
   add it to `$fillable` (security).

10. **Frontend booking CTA for telemedicine:**
    Shown whenever `telemedicine` module is enabled, regardless of
    `onlineDoctorsCount`. The fallback copy handles "no doctors yet".

---

## File layout highlights

```
app/
  Http/Controllers/
    Patient/            — patient portal (under {locale})
    Admin/              — admin portal
    Doctor/             — doctor portal
    Secretary/          — secretary portal
    HealthController.php  — public /health
  Services/
    ModuleManager.php
    OnlineConsultationService.php
    OnlineSlotGeneratorService.php
    Payment/
      PaymentGatewayManager.php
      Drivers/{PaymobGateway,StripeGateway}.php
  Models/               — Eloquent models (all use standard patterns)
  Http/Middleware/
    PatientAuth.php AdminAuth.php DoctorAuth.php SecretaryAuth.php
    CheckModule.php CheckPermission.php SetLocale.php
resources/
  js/
    Pages/              — Inertia page components (one per route)
    Layouts/            — 5 panel layouts (Patient/Doctor/Secretary/Admin/Webmaster)
    Components/
      FlashMessages.vue — global toast, in all 4 app layouts
      Telemedicine/VideoRoom.vue  — lazy-loaded Agora SDK (1.5 MB)
    Composables/
      useLocale.js useSettings.js usePermissions.js useTheme.js
  lang/{ar,en}.json     — app translation files
routes/
  web.php               — public frontend + /health
  patient.php           — patient portal (under {locale})
  admin.php doctor.php secretary.php webmaster.php
  console.php           — scheduled tasks (read this before adding cron logic)
database/
  migrations/           — idempotent, data-safe (see 2026_04_20_*)
  seeders/              — DemoSeeders for dev/staging only
docs/
  PRODUCTION_SETUP.md   — one-stop checklist for cPanel deploys
.github/workflows/
  ci.yml                — tests + build + manifest integrity
  deploy.yml            — auto-deploy on push to main (needs secrets)
```

---

## Definitely not

- **Don't delete** `public/build/` or add it to `.gitignore`.
- **Don't** run destructive migrations on prod without `--force` + a backup.
- **Don't** hardcode fees/URLs/credentials. Use `Setting::get()` or `.env`.
- **Don't** bypass patient/admin middleware with `auth()->loginUsingId()`
  in web controllers.

---

## Commit message style

Prefix with: `feat`, `fix`, `perf`, `style`, `chore`, `ci`, `test`, `docs`.
Include enough context in the body that future-you understands the *why*.
Every commit ends with the standard `Co-Authored-By` trailer when Claude
paired on it.
