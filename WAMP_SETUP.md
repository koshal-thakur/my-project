# WAMP + Database Setup (WEBSITEFRIENDS)

## 1) Put project in WAMP web root
- Copy this folder into: `C:/wamp64/www/WEBSITEFRIENDS`

## 2) Start WAMP services
- Open WampServer.
- Make sure icon is green.
- Confirm Apache and MySQL are running.

## 3) Open project in browser
- Public landing page: `http://localhost/WEBSITEFRIENDS/index2.html`
- Login page: `http://localhost/WEBSITEFRIENDS/LOGINpage.php`
- Logged-in home page: `http://localhost/WEBSITEFRIENDS/index.php`

## 4) Database connection used by app
Connection is centralized in `config.php`.
Default values:
- Host: `localhost`
- Port: `3306`
- User: `root`
- Password: empty
- Database: `quiz_competitors`

`config.php` will automatically:
- connect to MySQL,
- create the database if it does not exist,
- create required tables (`user`, `leaderboard`, `quiz_question`, `options`, `admin_login`).

## 5) Optional: use different DB credentials
Set environment variables before Apache starts:
- `DB_HOST`
- `DB_PORT`
- `DB_USER`
- `DB_PASS`
- `DB_NAME`

If not set, defaults above are used.

## 5.1) Razorpay API credentials (required for live payment)
Set these keys in project root `.env` file:
- `RAZORPAY_KEY_ID`
- `RAZORPAY_KEY_SECRET`

Example:
```
RAZORPAY_KEY_ID=rzp_test_your_key_id
RAZORPAY_KEY_SECRET=your_key_secret
```

The app auto-loads this `.env` file in `razorpay_api.php`.

### SSL certificate chain fix (self-signed certificate error)
If you get `SSL certificate problem: self-signed certificate in certificate chain`, add one of these in `.env`:

1) **Recommended (secure):** point to trusted CA bundle file
```
RAZORPAY_CA_BUNDLE=C:/path/to/cacert.pem
```
or
```
CURL_CA_BUNDLE=C:/path/to/cacert.pem
```

2) **Local dev only (not recommended for production):** disable SSL verification
```
RAZORPAY_SSL_NO_VERIFY=1
```

After updating `.env`, restart Apache in WAMP.

## 6) First login/admin
- Admin page: `http://localhost/WEBSITEFRIENDS/adminlogin.php`
- Default admin credentials inserted automatically on first run:
  - Username: `admin`
  - Password: `admin123`

## 7) Common troubleshooting
- If DB errors appear, verify MySQL service in WAMP is running.
- If port conflicts happen, check MySQL port in WAMP and match `DB_PORT`.
- If session/header warnings appear, ensure files are served by Apache (not opened directly from disk).
