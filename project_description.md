# Boniato School - Essential Context

## What This Is
Online language school management (~10 teachers, ~100 students, ~200 lessons/month). Replaces Google Forms → Sheets workflow.

## Authentication (Non-Standard)
**No Laravel guards.** Session-based auth only:
- `session('teacher_id')` - Teacher authenticated
- `session('admin_authenticated')` - Admin authenticated  
- Students: UUID direct links, no auth

**CSRF excluded** from login routes (`teacher/*/login`, `admin/login`) in `bootstrap/app.php`.

## Key Architecture Decisions
| Pattern | Implementation |
|---------|----------------|
| Data queries | `LessonRepository` |
| Business logic | Services (`BalanceService`, `AuthenticationService`) |
| Views | Livewire Volt components (`resources/views/livewire/`) |
| Reusable UI | Blade components (`resources/views/components/`) |
| Activity logs | `Spatie\ActivityLog` via `LogsActivityActions` concern |

## Database Schema (Key Tables)
- **students**: uuid, name, teacher_notes (TEXT), description
- **teachers**: name, password, contact, zoom_link, zoom_id, zoom_passcode
- **lessons**: student_id, teacher_id, scheduled_at, status (enum), topic, homework

## Google Sheets Integration
`BalanceService` syncs with external spreadsheet:
- Credentials: `storage/app/google-credentials.json`
- Tab: "Clients balance" with UUID (col S) and Paid classes (col Q)
- Balance = Paid - Completed lessons

## Production Environment
| Component | Details |
|-----------|---------|
| Host | Laravel Forge + DigitalOcean (Amsterdam 3) |
| Server | 2GB RAM, 1 vCPU, 50GB SSD ($14/mo) |
| Domain | t.leaguesofcode.space (Cloudflare DNS) |
| Database | MySQL (forge/forge) |
| Deploy | Auto on push to `master` |
| Backups | Daily → local + AWS S3 |
| Monitoring | Sentry (25% sample rate, expires Jan 2027) |
| SSL | Let's Encrypt (auto-renewed) |

## Production Commands
```bash
# After deploy - fix Livewire
npm ci && npm run build
php artisan livewire:publish --force

# Caches
php artisan optimize:clear   # clear all
php artisan optimize         # rebuild all

# Backups
php artisan backup:run       # manual backup
php artisan backup:list      # list all

# Logs
tail -f storage/logs/laravel.log

# SSH
ssh forge@server-ip
```

## Environment Variables (Production)
```bash
APP_DEBUG=false
ADMIN_PASSWORD=<strong-password>
GOOGLE_APPLICATION_CREDENTIALS=/home/forge/domain.com/storage/app/google-credentials.json
GOOGLE_SHEETS_BALANCE_SHEET_ID=<spreadsheet-id>

# Sentry (optional)
SENTRY_SAMPLE_RATE=0.25
SENTRY_TRACES_SAMPLE_RATE=0.1
```

## Design System
- **Buttons**: `px-4 py-2` (standard), `px-3 py-1` (small), `px-3 py-2` (input-aligned)
- **Styling**: Tailwind v4, check `resources/css/app.css` for variables
- **Dark mode**: Supported via `dark:` variants

## Files AI Should Ignore
`archive/`, `notes.md`, `TODO.md`, `Presentation/`
