# Production Setup Checklist

One-time setup steps for a fresh deploy of Doctorato on a cPanel host.
Ordered: do them top-to-bottom.

---

## 1. Laravel Scheduler (cron)

Without this, none of the scheduled commands in `routes/console.php` run
(bookings:send-reminders, backup:run, prune-old-logs, leads:daily-report,
dental:check-alerts, …).

Add via cPanel → **Cron Jobs** → **Add New Cron Job**:

- Common Settings: **Once per minute (`* * * * *`)**
- Command:
  ```
  cd /home/doctoratonet/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
  ```

Verify later with: `php artisan schedule:list`.

---

## 2. Payment Gateway

Required before the online-consultation booking flow can complete.

1. Go to `https://doctorato.net/ar/admin/settings/telemedicine`
2. Fill in one of:
   - **Stripe**: secret key + publishable key → "Test Stripe"
   - **Paymob**: API key + integration ID → "Test Paymob"
3. Save.
4. Confirm by hitting `GET /health` — `telemedicine.payment_gateway`
   should now be `"StripeGateway"` or `"PaymobGateway"`.

---

## 3. Agora (video SDK) credentials

Same admin settings page:

- **Agora App ID**
- **Agora App Certificate**
- Click "Test Agora".

---

## 4. GitHub Actions — auto-deploy secrets

Repo → Settings → Secrets and variables → Actions → New repository secret:

| Secret              | Value                                            |
|---------------------|--------------------------------------------------|
| `DEPLOY_HOST`       | Server hostname or IP (e.g. `srv.doctorato.net`) |
| `DEPLOY_USER`       | `doctoratonet`                                   |
| `DEPLOY_SSH_KEY`    | Private key (cPanel → SSH Access → Manage)       |
| `DEPLOY_PATH`       | `/home/doctoratonet/public_html`                 |
| `DEPLOY_PORT`       | (optional, default 22)                           |

Every push to `main` will then auto-deploy via `.github/workflows/deploy.yml`.

---

## 5. Health monitoring

Point any uptime monitor (UptimeRobot, Pingdom, BetterStack) at:

```
GET https://doctorato.net/health
```

- **200** → all checks pass.
- **503** → `body.checks.{name}.ok = false` shows which subsystem is down.

Throttled to 20 req/min per IP — safe for the typical 1/minute polling.

### 5.1 Email alerts

Beyond the external monitor, `php artisan health:alert` runs every 15
minutes and emails the admin on any degraded subsystem. Recipient order:

1. `Setting::set('health_alert_email', '…')` — optional dedicated address
2. Otherwise: first super_admin user's email

Same flow alerts weekly on `data:integrity-check` findings (orphans,
drift, stuck rows).

### 5.2 In-app operational page

Logged-in admins see a live health dot in the header on every page, and
`/admin/diagnostics` shows:
- System card (env, PHP/Laravel versions, DB, storage)
- Telemedicine card (blockers, counts, active gateway)
- Scheduler card (last run, healthy flag, copy-paste cron if inactive)
- Last 100 lines of laravel.log
- Export JSON button (for support tickets)

---

## 6. Sensitive file hygiene

Out of the box, `.gitignore` excludes `*.sql`, `backup_*`, `opcache-reset.php`,
`deploy.php`, `*.pem`, `*.key`, etc. Periodically audit the webroot:

```bash
cd /home/doctoratonet/public_html
ls -la | grep -E '\.(sql|bak|log|pem|key)$|opcache|deploy\.php|backup_'
```

Anything listed should be either removed or moved outside `public_html`.

---

## 7. File ownership / permissions

```bash
cd /home/doctoratonet/public_html
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
chmod 600 .env
```

---

## Verification

After completing steps 1-4, confirm with:

```bash
# Cron is scheduled
php artisan schedule:list | head -20

# Health is green
curl -s https://doctorato.net/health | python3 -m json.tool
```
