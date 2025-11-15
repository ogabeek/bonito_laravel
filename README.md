# Online School Management System (Laravel MVP)

## Project Overview

**Purpose:** Simplified management system for online school with ~10 teachers, ~100 students, handling ~200 classes/month. Replaces manual Google Forms → Spreadsheet workflow while preserving spreadsheet calculations for financial reporting.

**Context:** This is a pragmatic MVP built in 2 weeks. We chose speed over perfection. The codebase intentionally violates some best practices (like database normalization) to ship quickly.

**Current Status:** 2-week MVP implementation focusing on core functionality over architectural perfection.

**Key Principle:** Working product > Perfect code. Prioritizes rapid deployment 


## Technical Stack

- **Local Development:** Laravel Herd
- **Database:** SQLite (dev) 
- **Auth:** UUID-based direct links (no password system currently)
- **Sheets Integration:** revolution/laravel-google-sheets
- **CSS:** Tailwind 

## Core Features 

### 1. Teacher Portal (`/teacher/{tid}`)
- update attendance (completed/cancelled)
- topic name
- Homework
- Loging via given password 

### 2. Student/Parent View (`/student/{uuid}`)
- Direct link access (no password)
- View upcoming and past classes
- Extra: See current classes balance 
- Read-only access

### 3. Google Sheets Sync
- Hourly automatic export via cron
- Manual "Sync Now" button for immediate updates
- One-way sync (Laravel → Sheets only)
- Preserves existing spreadsheet formulas for calculations

### 4. Extra: Payment Tracking
- Manual entry after Stripe confirmation
- Balance = sum(payments) - sum(completed_classes * rate)



**Acceptable for MVP because:**
- Small user base (110 total users)
- Financial calculations remain in tested spreadsheet formulas
- Daily manual verification possible at this scale
- Can migrate to proper structure after validating system works


**Database**
I assume it's a relation many-to-many student may have different teachers and teachers many students, and admin taht can jange everything. Or as we have only one admin for now it's me so for simplisity maybe without creating a user I can access with teachers password if I need to change something 

## For AI Assistants

**About me**
i am doning my first steps on php and laravel, i use herd and just started, so help to go next, explain steps I want to understand, 
I don't want you to agentically cready or modify something, just tell me how to and why and I will. 
Feel free to use best practices on teaching to help with it 

**When helping with this codebase:**
- Assume we want the simplest solution that works
- Avoid suggesting major refactors unless critical
- Keep solutions compatible with existing denormalized structure
- Prioritize solutions that can be implemented in hours, not days
- Remember that Google Sheets handles complex financial calculations

**Current pain points:**
- Make a platform for Teachers to mark attendance, topic, HW  -> student see it -> admin have calculation of all classes
