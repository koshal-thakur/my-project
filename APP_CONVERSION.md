# Convert Quiz Competitors into an App

## 1) Make the web app live (required)
- Deploy this project to a public HTTPS host.
- Keep PHP + MySQL backend as-is.
- Make sure pages load from a stable URL, e.g. `https://yourdomain.com/questionsforyou/`.

## 2) PWA is already added
This project now includes:
- `manifest.webmanifest`
- `sw.js`
- `pwa-init.js`
- `offline.html`
- app icons in `icons/`

And key pages are wired to install as app.

## 3) Test install on Android (no APK yet)
1. Open site in Chrome Android.
2. Tap menu -> `Add to Home screen` / `Install app`.
3. Launch from homescreen.

## 4) Build APK/AAB for Play Store (recommended: PWABuilder)
1. Open: https://www.pwabuilder.com/
2. Enter your live HTTPS URL.
3. Click `Build My PWA`.
4. Choose `Android` and download generated `AAB`/`APK`.
5. Upload `AAB` to Google Play Console.

## 5) If install prompt does not appear
- Verify site is served via HTTPS.
- In Chrome DevTools -> Application:
  - Manifest is valid
  - Service Worker is active
- Clear cache and reload.

## 6) Production checklist
- Set strong DB credentials in environment variables.
- Disable default admin credentials from `config.php` seeded account.
- Add privacy policy and terms page before Play Store submission.
- Test login, quiz submit, and leaderboard on real mobile devices.
