Boniato School Management System 

**Purpose:** 
Simplified management system for online school with ~10 teachers, ~100 students, handling ~200 classes/month. Replaces manual Google Forms → Spreadsheet workflow while preserving spreadsheet calculations for financial reporting.

**Context:** 
This is a pragmatic MVP built in 2 weeks focusing on core functionality over architectural perfection.

---

### Technical Stack

- **Local Development:** Laravel Herd
- **Database:** SQLite (dev) -> Connected to TablePlus for gui 
- **Auth:** UUID-based direct links (no password system currently)
- **Sheets Integration:** Native Google API client in `BalanceService` (service account + sheet with `uuid`/`balance`)
- **CSS:** Tailwind and app.css file as a design references

---

### How to Setup

**Dependencies:** `composer install && npm install`  
**Environment:** `cp .env.example .env && php artisan key:generate`  
**Admin auth:** Set `ADMIN_PASSWORD=your_secret` in `.env` (no default)  
**Database:** `php artisan migrate`  
**Build:** `npm run build`  
**Access:** `http://boniato_check.test` (Herd auto-serves)

---

### Core Features 

**1. Teacher Portal (`/teacher/{tid}`)**
- Update attendance (completed/cancelled)
- Topic name
- Homework
- Login via given password 

**2. Student/Parent View (`/student/{uuid}`)**
- Direct link access (no password)
- View upcoming and past classes
- Extra: See current classes balance 
- Read-only access

**3. Google Sheets Sync**
- Balance import: `BalanceService` pulls `uuid/balance` from Google Sheets via service account (env: `GOOGLE_APPLICATION_CREDENTIALS`, `GOOGLE_SHEETS_BALANCE_SHEET_ID`, `GOOGLE_SHEETS_BALANCE_TAB`).
- Stats export: billing page has “Export to Sheet” to overwrite a tab (env: `GOOGLE_SHEETS_STATS_TAB`, same sheet ID). Uses native Google API client.
- One-way sync (Sheets → app for balances; app → Sheets for stats).


**Rationale for MVP because:**
- Small user base (110 total users)
- Financial calculations remain in tested spreadsheet formulas
- Daily manual verification possible at this scale
- Can migrate to proper structure after validating system works

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
- `resources/views/components/` - Reusable UI (lesson-card, lesson-form, calendar-picker, login-card)
- `public/css/app.css` - Design system (CSS variables)



### Recent Changes (summary)
- Added billing export button (admin billing page) writing stats to a single Google Sheet tab (configurable tab name).
- Simplified admin calendar (balance column removed), consistent sizing; lesson chips stack vertically.
- Shared monthly stats table component reused for student/teacher grids.
- Activity logs now include student names for easier matching.



<img src="image-1.png" alt="Alt Text" width="200" >



