# How to Get Your Sentry DSN (Step-by-Step)

## Method 1: From Setup Wizard (Easiest)

You're currently on the "Get Started with Sentry Issues" page. Follow these steps:

1. **You see:** "Install" (Step 1) ✅ - Already done!
2. **Click:** "Configure SDK" (Step 2) or "Next" button
3. **You'll see** a code block with:
```bash
SENTRY_LARAVEL_DSN=https://xxxxx@xxxx.ingest.sentry.io/xxxxx
```
4. **Copy** that entire URL (starting with `https://`)

---

## Method 2: From Project Settings

If you closed the setup wizard:

1. **Look at the left sidebar** in Sentry dashboard
2. Click the **⚙️ Settings** (gear icon at bottom left)
3. Click **Projects** in the left menu
4. Click your project: **"boniato"**
5. In the left menu under "SDK Setup", click **Client Keys (DSN)**
6. You'll see a box with your DSN
7. **Copy the DSN** - it looks like:
   ```
   https://abc123def456@o123456.ingest.sentry.io/7890123
   ```

---

## What the DSN Looks Like

Your DSN should look similar to this (but with different numbers):
```
https://1a2b3c4d5e6f7g8h9i0j@o987654.ingest.sentry.io/1234567
```

**Parts explained:**
- `1a2b3c4d5e6f7g8h9i0j` = Your public key
- `o987654` = Your organization ID
- `1234567` = Your project ID

---

## After You Get the DSN

### Option A: Test Locally (Optional)

1. Open `.env` file in your project
2. Add this line (replace with YOUR actual DSN):
```bash
SENTRY_LARAVEL_DSN=https://your-actual-dsn-here@o123.ingest.sentry.io/456
SENTRY_ENVIRONMENT=local
```

3. Test it:
```bash
php artisan config:clear
php artisan sentry:test
```

4. Check Sentry dashboard for test error

### Option B: Add to Production Only (Recommended)

Skip local testing and add directly to Forge:

1. Go to **Laravel Forge dashboard**
2. Select your site: **t.leaguesofcode.space**
3. Click **Environment** tab
4. Add these lines at the bottom:
```bash
SENTRY_LARAVEL_DSN=https://your-actual-dsn-here@o123.ingest.sentry.io/456
SENTRY_ENVIRONMENT=production
SENTRY_SAMPLE_RATE=0.25
SENTRY_TRACES_SAMPLE_RATE=0.1
```
5. Click **Save**

---

## Verify Setup in Sentry

After you deploy:

1. Visit your website: https://t.leaguesofcode.space
2. Trigger an error (e.g., visit: https://t.leaguesofcode.space/test-404)
3. Go back to Sentry dashboard
4. Click **Issues** in the left sidebar
5. You should see your first error! 🎉

---

## Troubleshooting

### Can't Find DSN in Sentry?
- Look for "Client Keys (DSN)" under Settings → Projects → boniato
- Or complete the onboarding wizard (step 2: Configure SDK)

### DSN Added but Test Fails?
```bash
# Clear cache and try again
php artisan config:clear
php artisan cache:clear
php artisan sentry:test
```

### Still Not Working?
- Make sure DSN starts with `https://`
- Check for extra spaces or quotes
- Verify you copied the entire DSN

---

## What to Do Now

1. ✅ Get your DSN from Sentry (Method 1 or 2 above)
2. ✅ Add to production via Forge (Option B)
3. ✅ Deploy changes:
```bash
git add .
git commit -m "Add Sentry integration"
git push origin master
```
4. ✅ Visit your site and check Sentry for captured errors

**You don't need to test locally - can add directly to production!**
