# Production Deployment Guide

## Current Production Setup
- **Hosting:** Laravel Forge + DigitalOcean
- **Server:** 2 GB RAM (Premium AMD) · 1 vCPU · 50 GB SSD ($14/mo)
- **Region:** Amsterdam 3
- **Domain:** t.leaguesofcode.space
- **DNS:** Cloudflare
- **Monitoring:** Sentry (Student plan, expires Jan 15, 2027)
- **Repository:** ogabeek/bonito_laravel (master branch)

---

## Deployment Steps

### 1. Environment Configuration (Laravel Forge)

In your Laravel Forge dashboard, set these environment variables:

```bash
APP_NAME="Boniato School"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://t.leaguesofcode.space
ADMIN_PASSWORD=your_secure_password_here

# Database (Forge provisions MySQL automatically)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=forge
DB_USERNAME=forge
DB_PASSWORD=auto_generated_by_forge

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Google Sheets Integration
GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json
GOOGLE_SHEETS_BALANCE_SHEET_ID=your_sheet_id
GOOGLE_SHEETS_BALANCE_TAB=Balance
GOOGLE_SHEETS_STATS_TAB=Stats

# Sentry Error Monitoring
SENTRY_LARAVEL_DSN=your_sentry_dsn_here
SENTRY_ENVIRONMENT=production
SENTRY_SAMPLE_RATE=0.25
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### 2. Deploy Application

Forge automatically:
- Pulls code from GitHub repository (master branch)
- Runs `composer install --no-dev --optimize-autoloader`
- Runs `npm install && npm run build`
- Configures Nginx with SSL (Let's Encrypt)

### 3. Database Migration & Seeding

SSH into server via Forge or run commands in Forge dashboard:

```bash
# Run migrations
php artisan migrate --force

# Seed the database
php artisan db:seed --force

# Or fresh install
php artisan migrate:fresh --seed --force
```

### 4. DNS Configuration (Cloudflare)

1. Add A record: `t.leaguesofcode.space` → Server IP
2. Enable Cloudflare proxy (orange cloud)
3. SSL/TLS mode: Full (strict)
4. Force HTTPS redirect

### 5. Storage & Permissions

```bash
php artisan storage:link
```

### 6. Optimize Caches

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Post-Deployment Checklist

- [x] Server created on DigitalOcean via Forge
- [x] Domain configured: t.leaguesofcode.space
- [x] DNS managed via Cloudflare
- [x] SSL certificate active (Let's Encrypt)
- [ ] Database migrated and seeded
- [ ] Admin login tested at `/admin`
- [ ] Teacher login tested at `/teacher/{id}`
- [ ] Student UUID links working
- [ ] Google Sheets integration configured
- [ ] Billing page functional

---

## Database Seeding Info

Current seed data (from `DatabaseSeeder.php`):
- **9 Teachers** (3 main + 6 secondary)
  - Main: maria123, john123, anna123
  - Secondary: carlos123, lin123, sophie123, ahmed123, emma123, yuki123
- **30 Students** with auto-generated UUIDs
- **~300+ Lessons** for November-December 2025
  - Started from November 10, 2025
  - 80% completed, 15-25 student cancelled, 5-10 teacher cancelled, 2-3 absent

---

## Continuous Deployment

Forge auto-deploys on push to master branch:

```bash
git add .
git commit -m "Your changes"
git push origin master
# Forge deploys automatically
```

Deploy script runs:
- `composer install --no-dev --optimize-autoloader`
- `npm run build`
- `php artisan migrate --force`
- `php artisan optimize`

---

## Troubleshooting

### Database Issues
```bash
php artisan migrate:fresh --seed --force
```

### Cache Issues
```bash
php artisan optimize:clear
php artisan optimize
```

### Permission Issues
```bash
chmod -R 775 storage bootstrap/cache
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Forge Server Access
SSH via Forge dashboard or:
```bash
ssh forge@server-ip
```

---

## Important Notes

⚠️ **Security:**
- `APP_DEBUG=false` in production
- Strong `ADMIN_PASSWORD` required
- Never commit `.env` to repository
- Cloudflare proxy enabled for DDoS protection

⚠️ **Database:**
- SQLite (local) → MySQL (production via Forge)
- Migrations handle schema automatically
- Seed data: 9 teachers, 30 students, ~300 lessons (Nov-Dec 2025)

⚠️ **Google Sheets:**
- Upload service account JSON to server
- Set `GOOGLE_APPLICATION_CREDENTIALS` path
- Configure sheet IDs in environment

⚠️ **DNS & SSL:**
- Domain: t.leaguesofcode.space
- DNS: Cloudflare (proxy enabled)
- SSL: Let's Encrypt (auto-renewed by Forge)

---

## Support

**Laravel Forge:**
- Dashboard: forge.laravel.com
- Server logs and monitoring available
- One-click SSL, deployments, backups

**DigitalOcean:**
- $200 student credit (14 months at $14/mo)
- Server: Amsterdam 3
- 2GB RAM (Premium AMD), 1 vCPU, 50GB SSD

**Application Issues:**
- Check `storage/logs/laravel.log`
- Verify environment variables in Forge
- Test database connection
