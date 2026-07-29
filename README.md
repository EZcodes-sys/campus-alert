# Campus Emergency Alert System (CEAS)

PHP 8 + MySQL + Bootstrap 5 — full MVP build

## Features Implemented

| Feature | Where |
|---|---|
| User registration & 4-tier role-based login (Admin, Security Officer, Lecturer, Student) | `auth/`, `includes/auth.php` |
| Admin alert broadcasting via in-app notifications + email | `admin/alerts.php`, `includes/alerts.php`, `includes/mailer.php` |
| Real-time-style alert dashboard for all users (AJAX polling, no page reload) | `dashboard.php`, `alerts.php`, `api/alerts_feed.php` |
| Incident reporting with status tracking | `incidents/report.php`, `incidents/my_incidents.php`, `admin/incidents.php` |
| Emergency contact directory | `contacts.php`, `admin/contacts.php` |
| Evacuation information display | `evacuation.php` |
| Profile management & RBAC administration | `profile.php`, `admin/users.php` |

## 1. Requirements
- XAMPP (Apache + PHP 8.x + MySQL) — https://www.apachefriends.org
- A browser

## 2. Setup Steps

1. Copy the whole `campus_alert` folder into your XAMPP web root:
   - Windows: `C:\xampp\htdocs\campus_alert`
   - Linux: `/opt/lampp/htdocs/campus_alert`

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Open `http://localhost/phpmyadmin`, click **New**, and import `database.sql`
   (or use the SQL tab, paste the contents, click **Go**). This creates
   `campus_alert_db` with 6 tables — `users`, `alerts`, `incidents`,
   `notifications`, `emergency_contacts`, `site_settings` — plus 4 demo
   accounts, 3 emergency contacts, and default evacuation content.

4. If your MySQL root user has a password, set it in `includes/config.php`:
   ```php
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password_here');
   ```

5. Visit `http://localhost/campus_alert/`.

## 3. Demo Accounts (all use password `Admin@1234`)

| Role              | Email                    |
|-------------------|--------------------------|
| Administrator     | admin@campus.ac.ke       |
| Security Officer  | security@campus.ac.ke    |
| Lecturer          | lecturer@campus.ac.ke    |
| Student            | student@campus.ac.ke    |

## 4. Email Alerts (Optional — works out of the box without this)

By default `MAIL_ENABLED` is `false` in `includes/config.php`. In this mode,
every alert email is safely **logged to `logs/mail.log`** instead of sent —
so the broadcast feature always works during a demo, even with no internet
or SMTP account. Open `logs/mail.log` after broadcasting an alert to show
your lecturer exactly what each recipient's email would have contained.

To send **real emails**:
1. Get a Gmail account and generate an **App Password** (Google Account →
   Security → 2-Step Verification → App Passwords).
2. In `includes/config.php`, set:
   ```php
   define('MAIL_ENABLED', true);
   define('SMTP_USER', 'youraccount@gmail.com');
   define('SMTP_PASS', 'your16charapppassword');
   define('SMTP_FROM_EMAIL', 'youraccount@gmail.com');
   ```
3. Real PHPMailer (bundled in `vendor/phpmailer/`, no Composer needed) will
   send via Gmail's SMTP server. Failed sends still fall back to
   `logs/mail.log` automatically, so a bad connection never breaks the demo.

## 5. Suggested Demo Flow (for your lecturer)

1. **Register** a new account → validation on weak password / duplicate email.
2. **Login as admin** (admin@campus.ac.ke) → Admin Dashboard.
3. **Broadcast Alerts** → send a "Fire Drill" alert (severity: high). Show the
   delivery summary (in-app + email counts) and open `logs/mail.log` to prove
   the email content was generated.
4. **Login as student** (in another browser/incognito tab) → the nav bell
   shows an unread badge immediately; the Dashboard's "Active Alerts" panel
   updates on its own every ~10 seconds via AJAX polling — no page refresh.
   Click into **Alerts** to view/read it in full.
5. **Report an Incident** as the student → get a tracking code
   (`INC-2026-0001`) → check **My Incidents** to see it listed as "Open".
6. **Login as Security Officer** → open **Manage Incidents**, change the
   status to "Investigating" → switch back to the student tab and refresh
   **My Incidents** to show the status updated live in the database.
7. Show **Manage Users** (admin only) → promote a student to lecturer.
8. Try visiting `/admin/alerts.php` or `/admin/users.php` while logged in as
   a student or security officer → redirected away, proving RBAC enforcement
   (`requireRole()` / `requireAdmin()` guards).
9. Open **Emergency Contacts** and **Evacuation Info** — show the admin-only
   edit form on Evacuation Info versus the read-only view for other roles.

## 6. File Structure

```
campus_alert/
├── includes/
│   ├── config.php        ← DB/session/SMTP config
│   ├── db.php              ← PDO singleton
│   ├── auth.php             ← registration, login, CSRF, RBAC guards, password reset
│   ├── alerts.php           ← broadcastAlert(), notification fan-out
│   ├── incidents.php        ← submitIncident(), status updates, tracking codes
│   ├── mailer.php           ← PHPMailer wrapper w/ safe log fallback
│   ├── header.php / footer.php  ← shared layout, nav, live-poll bell script
├── auth/                    ← register, login, logout, forgot/reset password
├── admin/
│   ├── dashboard.php         ← stats overview
│   ├── users.php              ← RBAC: role & account management
│   ├── alerts.php              ← broadcast alerts + delivery history
│   ├── incidents.php            ← admin/security: manage all incidents
│   └── contacts.php              ← CRUD emergency contacts
├── incidents/
│   ├── report.php              ← submit an incident (any logged-in user)
│   └── my_incidents.php          ← track your own reports
├── api/
│   ├── alerts_feed.php           ← JSON polling endpoint (real-time dashboard)
│   └── mark_read.php              ← mark an alert read
├── vendor/phpmailer/               ← bundled PHPMailer source (no Composer needed)
├── logs/mail.log                    ← simulated/failed email log
├── assets/css/
├── dashboard.php, profile.php, alerts.php, contacts.php, evacuation.php, index.php
└── database.sql                      ← full schema + seed data
```

## 7. Security Features
- bcrypt password hashing (cost 12)
- CSRF tokens on every POST form and API call
- PDO prepared statements everywhere
- Session fixation prevention, 30-min idle timeout, HttpOnly + SameSite cookies
- RBAC guards: `requireLogin()`, `requireRole()`, `requireAdmin()`
- Password reset via single-use, hashed, 1-hour-expiring tokens

## 8. Notes on "Real-Time"
The dashboard and nav bell use **AJAX short-polling** (every 10-15 seconds)
against `api/alerts_feed.php` rather than WebSockets/Server-Sent Events. This
is a standard, well-understood approach for a student project on shared
hosting/XAMPP (no persistent socket server required) and gives a genuinely
live-updating UI. If your lecturer specifically asks about scaling this to
true push-based delivery, mention WebSockets (e.g. Ratchet/Pusher) or
Server-Sent Events as the natural next step — documented here as a known
limitation, not hidden.
