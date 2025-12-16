# Laravel Cloud Deployment Guide

## Prerequisites
✅ Laravel Cloud account created
✅ GitHub repository connected to Laravel Cloud
✅ Repository: `ogabeek/bonito_laravel`

---

## Deployment Steps

### 1. Configure Environment in Laravel Cloud Dashboard

In your Laravel Cloud dashboard, set these environment variables:

```bash
APP_NAME="Boniato School"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.laravel.cloud
ADMIN_PASSWORD=your_secure_password_here

# Database (Laravel Cloud provides MySQL automatically)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=boniato_school
DB_USERNAME=forge
DB_PASSWORD=your_db_password_here

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Google Sheets Integration (if needed)
GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json
GOOGLE_SHEETS_BALANCE_SHEET_ID=your_sheet_id
GOOGLE_SHEETS_BALANCE_TAB=Balance
GOOGLE_SHEETS_STATS_TAB=Stats
```

### 2. Deploy Application

Laravel Cloud will automatically:
- Pull code from your GitHub repository
- Run `composer install`
- Run `npm install && npm run build`
- Set up the database

### 3. Run Database Migrations & Seeding

After initial deployment, SSH into your Laravel Cloud instance or use the Cloud CLI:

```bash
# Run migrations
php artisan migrate --force

# Seed the database with test data
php artisan db:seed --force

# Or fresh migrate + seed
php artisan migrate:fresh --seed --force
```

### 4. Configure Database Settings

Laravel Cloud automatically provisions a MySQL database. Make sure to:
1. Go to Database section in Laravel Cloud dashboard
2. Note the database credentials
3. Update environment variables with correct DB credentials

### 5. Set Up Storage (if needed)

```bash
php artisan storage:link
```

### 6. Clear & Optimize Caches

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Post-Deployment Checklist

- [ ] App is accessible at your Laravel Cloud URL
- [ ] Admin login works at `/admin` with your ADMIN_PASSWORD
- [ ] Database has teachers and students
- [ ] Teachers can log in at `/teacher/{id}` with seeded passwords
- [ ] Students can access their portals via UUID links
- [ ] Billing page displays charts correctly

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

Laravel Cloud will automatically redeploy when you push to the `master` branch:

```bash
# Make changes locally
git add .
git commit -m "Your changes"
git push origin master

# Laravel Cloud will auto-deploy
```

---

## Troubleshooting

### Database Issues
If database doesn't seed properly:
```bash
# SSH into Laravel Cloud instance
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
# In Laravel Cloud dashboard
tail -f storage/logs/laravel.log
```

---

## Important Notes

⚠️ **Security:**
- Keep `APP_DEBUG=false` in production
- Use strong `ADMIN_PASSWORD`
- Never commit `.env` file to repository

⚠️ **Database:**
- SQLite (local dev) → MySQL (production)
- Database migrations handle this automatically
- Seed data creates November-December 2025 lessons only

⚠️ **Google Sheets:**
- Upload service account JSON to Laravel Cloud
- Set correct path in `GOOGLE_APPLICATION_CREDENTIALS`
- Configure sheet IDs in environment variables

---

## Support

For Laravel Cloud specific issues:
- Check Laravel Cloud documentation
- Use dashboard logs and monitoring
- Contact Laravel Cloud support

For application issues:
- Check `storage/logs/laravel.log`
- Review database connections
- Verify environment variables
