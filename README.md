Boniato School Management System 

**Purpose:** 
Simplified management system for online school with ~10 teachers, ~100 students, handling ~200 classes/month. Replaces manual Google Forms → Spreadsheet workflow while preserving spreadsheet calculations for financial reporting.

### Technical Stack

- **Local Development:** Laravel Herd
- **Database:** SQLite (dev) → MySQL (production)
- **Auth:** UUID-based direct links (students), password-based (teachers/admin)
- **Sheets Integration:** Native Google API client in `BalanceService` (service account + sheet with `uuid`/`balance`)
- **Monitoring:** Sentry error tracking
- **Backups:** Automated daily backups (spatie/laravel-backup)
- **CSS:** Tailwind and app.css file as design references

### How to Setup

**Dependencies:** `composer install && npm install`  
**Environment:** `cp .env.example .env && php artisan key:generate`  
**Admin auth:** Set `ADMIN_PASSWORD=your_secret` in `.env` (no default)  
**Database:** `php artisan migrate`  
**Build:** `npm run build`  
**Access:** `http://boniato_check.test` (Herd auto-serves)

### Core Features 

**1. Teacher Portal (`/teacher/{tid}`)**
- Update attendance (completed/cancelled)
- Topic name
- Homework
- See basic montth stats
- Login via given password 

**2. Student/Parent View (`/student/{uuid}`)**
- Direct link access (no password)
- View upcoming and past classes
- Read-only access

**3. Admin Portal (`/admin`)**
 - logs
 - calendat view of the everyday class
 - statistics page 
 - managing students and teachers

**4. Google Sheets Sync**
- Balance import: `BalanceService` pulls `uuid/balance` from Google Sheets via service account
- Stats export: billing page "Export to Sheet" button writes to configured tab
- One-way sync (Sheets → app for balances; app → Sheets for stats)

**5. Automated Backups**
- Twice daily: 4:30 AM and 4:30 PM CET (Europe/Madrid timezone)
- Retention: 360 days (all), 720 days (daily), 3 years (monthly)
- Storage: `storage/app/private/Laravel/` (local), future: remote backup server
- Package: spatie/laravel-backup
- Scheduler: Laravel cron (Forge handles automatically)


**Database:**
- Many-to-many relationship: students ↔ teachers (via `student_teacher` pivot table)
- Each teacher only sees their assigned students
- Teachers: `id`, `name`, `password`
- Students: `id`, `uuid`, `name`, `parent_name`, `email`, `goal`, `description`
- Lessons: `teacher_id`, `student_id`, `class_date`, `status` (completed/student_absent/teacher_cancelled), `topic`, `homework`, `comments`

### Project Structure

**Key Files:**
- `app/Http/Controllers/` - AdminController, TeacherController, StudentController
- `app/Http/Requests/` - Form validation (CreateLessonRequest, UpdateLessonRequest)
- `app/Models/` - Teacher, Student, Lesson (with relationships)
- `resources/views/layouts/app.blade.php` - Base layout for all pages
- `resources/views/components/` - Reusable UI (lesson-card, lesson-form, calendar-picker, info-banner)
- `public/css/app.css` - Design system (CSS variables)

**UI Components:**
```blade
<!-- Info Banner - for help text, tips, and welcome messages -->
<x-info-banner type="tip" dismissible>
    Welcome message or helpful tip here
</x-info-banner>

<!-- Types: info (blue), success (green), warning (orange), tip (purple) -->
<!-- Add dismissible to allow users to close it -->
```

### Recent Changes (summary)
- Added billing export button (admin billing page) writing stats to a single Google Sheet tab (configurable tab name).
- Simplified admin calendar (balance column removed), consistent sizing; lesson chips stack vertically.
- Shared monthly stats table component reused for student/teacher grids.
- Activity logs now include student names for easier matching.

**Notes:**
- Small user base (110 total users)
- Financial calculations remain in tested spreadsheet formulas

**Current pain points:**
Make a platform for Teachers to mark attendance, topic, HW  -> student see it -> admin have calculation of all classes

<img src="image-1.png" alt="Alt Text" width="200" >



TODO: 
- delete student (e.g. accidentally created Rafael G, how to delete it now) 
- welcome message add note section about how to start
- Decide about "Notes " at teachers pannel 
- setUp Sentry dashboard and tracked errors 







