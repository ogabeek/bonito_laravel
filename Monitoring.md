# Application Monitoring

## Sentry Error Tracking

**Plan:** Student Developer (expires Jan 15, 2027)
**Limits:** 5,000 errors/month, 50 replays/month, 5M spans/month, 5GB logs/month

### Production Configuration
```bash
SENTRY_LARAVEL_DSN=https://b7c6b641909f10e37319f6f112e82a78@o4510713480544256.ingest.de.sentry.io/4510713949061200
SENTRY_ENVIRONMENT=production
SENTRY_SEND_DEFAULT_PII=false
SENTRY_SAMPLE_RATE=0.25
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### Budget Optimization (12+ months coverage)
- Error sampling: 25% (captures 1 in 4 errors)
- Trace sampling: 10% (performance monitoring)
- PII: Disabled (privacy/GDPR compliant)
- Breadcrumbs: Disabled (saves quota)
- SQL tracing: Disabled (saves spans)

**Rationale:** With ~110 users and ~50-200 errors/month, 25% sampling = 13-50 tracked errors/month (well under 5,000 limit)

### Setup
- Package: `sentry/sentry-laravel`
- Bootstrap: `bootstrap/app.php` uses `Integration::handles($exceptions)`
- Config: `config/sentry.php` (optimized defaults)
- Test: `php artisan sentry:test`

### Get DSN
Sentry Dashboard → Settings → Projects → boniato → Client Keys (DSN)

### Dashboard
https://sentry.io → Project: boniato

### Adjust if Needed
Increase sampling when traffic grows or during critical periods via .env:
```bash
SENTRY_SAMPLE_RATE=0.5  # 50% of errors
SENTRY_TRACES_SAMPLE_RATE=0.2  # 20% of traces
```
