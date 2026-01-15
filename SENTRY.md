# Sentry Error Monitoring Setup

## Student Plan Details
- **Plan:** Developer (until Jan 15, 2027)
- **Reserved Errors:** 5,000/month
- **Reserved Replays:** 50/month
- **Reserved Spans:** 5,000,000/month
- **Reserved Logs:** 5 GB/month

---

## Budget-Optimized Configuration

### Sample Rates (configured for 1-year usage):
- **Error Sample Rate:** 25% (sample_rate: 0.25)
- **Traces Sample Rate:** 10% (traces_sample_rate: 0.1)
- **Profiles Sample Rate:** 10% (profiles_sample_rate: 0.1)
- **Logs:** Disabled (enable_logs: false)

### Disabled Features (to save quota):
- ❌ Breadcrumbs (logs, cache, SQL, queue, commands, HTTP, notifications)
- ❌ SQL query tracing
- ❌ Queue job tracing
- ❌ Performance spans for SQL

### What You Still Get:
- ✅ 25% of all errors captured (enough for 110 users)
- ✅ Stack traces and error context
- ✅ Release tracking
- ✅ Environment separation (production/staging)
- ✅ Basic performance monitoring (10% sample)

---

## Setup Instructions

### 1. Add to .env (Local)
```bash
SENTRY_LARAVEL_DSN=your_sentry_dsn_here
SENTRY_ENVIRONMENT=local
```

### 2. Add to Production (Forge)
```bash
SENTRY_LARAVEL_DSN=your_sentry_dsn_here
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=1.0.0

# Optional: Override defaults if needed
SENTRY_SAMPLE_RATE=0.25
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1
```

### 3. Get Your DSN
1. Go to Sentry dashboard
2. Select your project
3. **Settings** → **Client Keys (DSN)**
4. Copy DSN URL

### 4. Test Integration
```bash
# Local test
php artisan sentry:test

# Check logs
tail -f storage/logs/laravel.log
```

### 5. Deploy
```bash
git add .
git commit -m "Add Sentry monitoring"
git push origin master
```

---

## Estimated Usage (110 users, low traffic)

### Monthly Estimates:
- **Errors:** ~50-200/month (under 5,000 limit)
- **Page Views:** ~2,000/month
- **Sampled Errors:** 50-200 × 25% = 13-50 errors tracked
- **Sampled Traces:** 2,000 × 10% = 200 traces tracked

### With These Settings:
✅ **12+ months coverage** before hitting limits
✅ Captures critical errors
✅ Enough data for debugging
✅ Room for traffic growth

---

## When to Increase Sampling

**Increase sample rates if:**
- Traffic grows significantly (3x-5x users)
- Need more detailed debugging
- Critical launch period
- Under 50% quota usage

**Adjust via .env:**
```bash
SENTRY_SAMPLE_RATE=0.5  # 50% errors
SENTRY_TRACES_SAMPLE_RATE=0.2  # 20% traces
```

---

## Monitoring Your Quota

**Check usage in Sentry:**
1. Dashboard → **Stats**
2. View errors/replays/spans consumed
3. Adjust sample rates if needed

**Alert thresholds:**
- 🟢 0-60%: Optimal
- 🟡 60-80%: Monitor closely
- 🔴 80-100%: Reduce sample rates

---

## Important Notes

⚠️ **Local Development:**
- Sentry will capture local errors too
- Use `SENTRY_ENVIRONMENT=local` to separate
- Can disable locally: comment out DSN in .env

⚠️ **Privacy:**
- `send_default_pii: false` (no user emails/IPs by default)
- Safe for GDPR/privacy compliance

⚠️ **Performance:**
- Minimal impact with 25% sampling
- Async error reporting (non-blocking)

---

## Custom Error Tracking (Optional)

```php
// In your controller
try {
    // Your code
} catch (\Exception $e) {
    // Log to Sentry with context
    \Sentry\captureException($e, [
        'extra' => [
            'user_id' => $userId,
            'action' => 'lesson_update',
        ],
    ]);
    
    // Still show user-friendly error
    return back()->with('error', 'Something went wrong');
}
```

---

## Troubleshooting

### Not Seeing Errors in Sentry:
```bash
# Verify DSN is set
php artisan config:clear
php artisan config:cache

# Test manually
php artisan sentry:test
```

### Too Many Errors Captured:
```bash
# Reduce sample rate in .env
SENTRY_SAMPLE_RATE=0.1  # Only 10%
```

### Check Configuration:
```bash
php artisan config:show sentry
```
