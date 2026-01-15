# Production Environment Variables for Forge

Add these to your Laravel Forge → Environment tab:

```bash
SENTRY_LARAVEL_DSN=https://b7c6b641909f10e37319f6f112e82a78@o4510713480544256.ingest.de.sentry.io/4510713949061200
SENTRY_ENVIRONMENT=production
SENTRY_SEND_DEFAULT_PII=false
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_SAMPLE_RATE=0.25
```

## Why These Settings:

- **SENTRY_ENVIRONMENT=production** - Separates prod from local errors
- **SENTRY_SEND_DEFAULT_PII=false** - Privacy/GDPR compliant (no emails/IPs)
- **SENTRY_TRACES_SAMPLE_RATE=0.1** - Only 10% traces = saves quota
- **SENTRY_SAMPLE_RATE=0.25** - Only 25% errors = 12+ months coverage

## Deploy Steps:

1. Go to Forge → Your Site → Environment
2. Scroll to bottom and paste the 5 lines above
3. Click Save
4. Deploy:
```bash
git add .
git commit -m "Add Sentry monitoring with budget-optimized config"
git push origin master
```

## After Deploy:

Visit https://t.leaguesofcode.space and check Sentry dashboard for captured events!
