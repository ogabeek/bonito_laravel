# Boniato School Management System

**Purpose:**
Simplified management system for an online school (~10 teachers, ~100 students,
~200 classes/month). Replaces the manual Google Forms → Spreadsheet workflow
while still honouring the spreadsheet as the source of truth for payments and
financial reporting.

The core loop: **teachers mark attendance / topic / homework → students &
parents see it on a private link → admin gets the calendar, stats and balances
across all classes.**

---

## Technical Stack

- **Framework:** Laravel 12 (PHP 8.4)
- **UI:** Livewire 4 / Volt single-file components + Blade, Tailwind CSS 4 via Vite
- **Local Development:** Laravel Herd
- **Database:** MySQL (dev, seeded from a prod snapshot, and production); SQLite `:memory:` for tests
- **Auth:** UUID direct links (students, no password); PIN/password (teachers & admin)
- **Sheets Integration:** native Google API client (`GoogleSheetsClient`) reading a
  service-account spreadsheet for paid-class balances and per-payment history
- **Monitoring:** Sentry error tracking
- **Backups:** twice-daily automated backups (spatie/laravel-backup) to local + S3
- **Quality gates:** Pest tests, Pint (style), PHPStan (static analysis), GitHub Actions CI

---

## Setup

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# Admin auth: set a secret (no default). Plain text or a bcrypt hash both work.
#   ADMIN_PASSWORD=your_secret
php artisan migrate
npm run build
```

Access locally at `http://boniato_check.test` (Herd auto-serves).

---

## Core Features

**1. Teacher Portal (`/teacher/{id}`)** — PIN login
- Mark attendance: completed / student absent / cancelled (by student or teacher)
- Topic, homework, comments per lesson
- Absence follow-ups (reminder + recovery tracking) for absent lessons
- Monthly stats for their own students; sees only assigned students
- "Help improve this space" — a private feedback thread with the admin

**2. Student / Parent View (`/student/{uuid}`)** — direct link, no login
- Upcoming and past classes, grouped by month
- Lesson topic, homework, teacher materials
- Weekly class distribution chart; read-only

**3. Admin Portal (`/admin`)** — password login
- Calendar view of every class; per-student and per-teacher monthly stats
- Manage students & teachers (create, edit, assign, **archive/restore** via soft deletes)
- Student status (active / inactive / holiday / finished / dropped), status notes,
  and holiday/vacation date ranges
- Activity logs (with student names for easy matching)
- Billing / stats page (`/admin/billing`) and feedback inbox

**4. Balance & Payment Ledger**
- Paid classes come from Google Sheets; "used" classes are chargeable lessons in the DB
- Balance = paid − used, computed live (negative = student owes)
- **Per-student ledger** (`BalanceLedgerService`) merges dated payments with lessons
  into a running balance from a configurable journal start (`BILLING_JOURNAL_START`)
- `php artisan billing:ledger-check` reconciles every student and flags issues
  (no balance data, negative opening, mismatches, unmatched payment rows)

**5. Google Sheets Integration**
- **Balances tab** (`GOOGLE_SHEETS_BALANCE_TAB`): columns `uuid`, `Paid Classes`
- **Payments tab** (`GOOGLE_SHEETS_PAYMENTS_TAB`, default `Payments`): columns
  `Date`, `Student`, `Number of hours` (a blank first row is tolerated)
- Stats export back to the sheet (`StatsExportService`)
- Reads are cached 5 min and **never cache an empty result** (a transient API
  timeout must not blank balances for the whole TTL — see `CachesNonEmpty`)
- *Caveat:* payments are matched to students by normalised **name**; align sheet
  names with student names (UUID matching is the planned improvement)

**6. Teacher ↔ Admin Feedback**
- One ongoing thread per teacher (`feedback_threads` / `feedback_messages`),
  open/resolved status, unread badges

**7. Automated Backups**
- Twice daily 04:30 & 16:30 CET (Europe/Madrid)
- Retention: 360 days (all), 720 days (daily), 3 years (monthly)
- Dual storage: local `storage/app/private/Boniato School/` + S3
  `s3://boniato-school-backups/backups/`
- Scheduler: Laravel cron via `/etc/cron.d/laravel-scheduler` (`schedule:run` each minute)

---

## Authentication model

`AuthenticationService::verifyPassword()` accepts **both** a legacy bcrypt hash
(`Hash::check`) and a plain-text PIN (`hash_equals`), so admin can read/share a
teacher's PIN while older hashed credentials keep working. Login routes enforce
CSRF; Livewire re-checks admin/teacher auth on every request
(`addPersistentMiddleware`).

---

## Data model

- **students** ↔ **teachers**: many-to-many via `student_teacher`; each teacher sees
  only assigned students. Soft-deleted (archived) students stay out of teacher and
  admin calendar views but keep their lessons & assignments.
- **Teachers:** `id`, `name`, `password` (PIN), contact + Zoom fields, soft deletes
- **Students:** `id`, `uuid`, `name`, `parent_name`, `email`, `goal`, `description`,
  `status` (+ `status_note`), `vacation_starts_on/ends_on`, `country`, `language`,
  `materials_url`, `teacher_notes`, soft deletes
- **Lessons:** `teacher_id`, `student_id`, `class_date`,
  `status` (completed / student_absent / student_cancelled / teacher_cancelled),
  `topic`, `homework`, `comments`, absence-follow-up + `refund_requested`, soft deletes
- **Feedback:** `feedback_threads` (teacher, status) + `feedback_messages` (sender,
  body, read_at)

---

## Architecture

- **Controllers** (`app/Http/Controllers/`): thin; `Admin\StudentController`,
  `Admin\TeacherController`, `Admin\AuthController`, `TeacherController`, `StudentController`
- **Form Requests** (`app/Http/Requests/`): validation (e.g. `CreateLessonRequest`)
- **Repository** (`app/Repositories/LessonRepository`): all lesson queries in one place
- **Services** (`app/Services/`):
  - Sheets/billing: `GoogleSheetsClient`, `BalanceService`, `PaymentsService`,
    `BalanceLedgerService`, `StudentBalanceService`, `BillingDataService`, `StatsExportService`
  - Stats/data: `LessonStatisticsService`, `TeacherStatsService`, `DashboardDataService`, `CalendarService`
  - `AuthenticationService`
- **Concerns** (`app/Concerns/`): `LogsActivityActions`, `ArchivesRecords`
  (shared archive/restore), `CachesNonEmpty` (don't-cache-empty caching)
- **Models** (`app/Models/`): `Teacher`, `Student`, `Lesson`, `FeedbackThread`, `FeedbackMessage`
- **Views**: `resources/views/livewire/` (Volt components: admin/teacher dashboards,
  billing, feedback), `resources/views/components/` (reusable UI: `lesson-card`,
  `student-stats-list`, `student-ledger`, status controls, `info-banner`)
- **Design tokens / CSS:** `resources/css/app.css`

**UI Components example:**
```blade
<!-- Info Banner — help text, tips, welcome messages -->
<x-info-banner type="tip" dismissible>
    Welcome message or helpful tip here
</x-info-banner>
<!-- type: info (blue) | success (green) | warning (orange) | tip (purple) -->
```

---

## Testing & quality

```bash
php artisan test            # Pest (Unit + Feature) on sqlite :memory:
vendor/bin/pint --test app  # code style
vendor/bin/phpstan analyse  # static analysis
```
GitHub Actions (`.github/workflows/ci.yml`) runs Pint → PHPStan → build → tests on push.

---

## Deployment (production)

Manual, backup-first, fail-safe via `./deploy.sh` in the site root:

1. Enable maintenance mode
2. Integrity-checked `mysqldump` to `/home/forge/backups/` (deploy aborts if the
   backup is bad; old pre-deploy backups pruned after 30 days)
3. `git pull --ff-only` → `composer install --no-dev --optimize-autoloader`
4. `npm ci && npm run build`
5. `php artisan migrate --force` → rebuild config/route/view caches
6. Reload PHP-FPM → disable maintenance mode

On **any** failure the script stays in maintenance mode and prints the backup
path. Recover (fix or restore backup + `git reset --hard`), then `php artisan up`.

Public traffic is proxied through Cloudflare; TLS via Let's Encrypt (certbot).

---

## Notes

- Small user base (~110 total). Payments/financials still live in the trusted
  spreadsheet; the app reads and reconciles them rather than replacing them.
- Original pain point (now delivered): *teachers mark attendance, topic, HW →
  students see it → admin has the calculation of all classes.*

<img src="docs/image-1.png" alt="Boniato dashboard" width="200">
