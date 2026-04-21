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
  clinic module. Slugs: `derma`, `dental`, `pediatric`, `telemedicine`.
  `MEDICAL_MODULES = ['derma','dental','pediatric']` — only these have
  doctors/visits/bookings. Enable with `ModuleManager::enable('slug')`.

- **Settings** (`app/Models/Setting.php`) — key/value table, used for
  everything runtime-configurable (clinic name, payment keys, Agora
  credentials, fees, …). Encrypted keys are marked via
  `Setting::isEncryptedKey()`.

- **Module sidebar filtering** — Layout sidebars filter sections via a
  `moduleKey` field matching `modules.value[key]?.enabled === true`.

- **Week convention** — `day_of_week`: `0=Saturday, 1=Sunday, …, 6=Friday`.
  Different from Carbon's `dayOfWeek` (0=Sun). Use the map in
  `OnlineSlotGeneratorService::carbonToSystemDay()` to translate.

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
./serve.sh        # or: php artisan serve + npm run dev

# Build for prod (manifest.json is committed — see CI)
npm run build

# Tests (requires phpunit — add to composer if missing)
vendor/bin/phpunit --filter=OnlineConsultationBookingTest

# Anything in routes/console.php
php artisan schedule:list
```

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
