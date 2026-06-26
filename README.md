# MediConnect

A telemedicine platform built for Zambia — connecting patients with licensed doctors through online consultations, digital prescriptions, and real-time chat, all in one place.

This is a third-year university project, but it's built to feel like a real product. The goal was to solve an actual problem: a lot of people in Zambia travel long distances just to see a doctor for something that could be handled remotely. MediConnect is an attempt to fix that.

---

## What it does

Patients can search for doctors by specialisation, book appointments, and jump into consultations from their browser — no app to install, no waiting room. Doctors get a dashboard to manage their schedule, run consultations, and write digital prescriptions. Pharmacies can verify and dispense those prescriptions. Admins keep everything running: approving doctors, managing facilities, and pulling reports.

**Four roles, one platform:**

| Role | What they can do |
|---|---|
| Patient | Find doctors, book & cancel appointments, join consultations, view health records and prescriptions |
| Doctor | Manage availability, run consultations (chat/voice/video), write prescriptions, view patient history |
| Pharmacy | Scan and verify prescriptions, mark as dispensed |
| Admin | Approve doctors, manage users and facilities, view system reports |

Payments go through MTN MoMo, Airtel Money, or Zamtel Kwacha. All fees are displayed in ZMW.

---

## Stack

- **Backend:** PHP 8+ (no framework — vanilla PHP with a front-controller router)
- **Database:** MySQL 8
- **Frontend:** Plain HTML/CSS/JS — no React, no build step
- **Server (dev):** PHP built-in server

The routing works like this: every request hits `public/index.php`, which matches the URL to a PHP file and wraps it in the shared layout (header, navbar, footer). No `.htaccess` magic needed for development.

---

## Getting started

You'll need PHP 8+ and MySQL running locally.

**1. Clone and set up the database**

```bash
mysql -u root -p < schema.sql
```

This creates the `mediconnect` database and all the tables.

**2. Create a database user** (or use root for local dev)

```sql
CREATE USER 'telemed'@'localhost' IDENTIFIED BY 'telemed2026T#';
GRANT ALL PRIVILEGES ON mediconnect.* TO 'telemed'@'localhost';
FLUSH PRIVILEGES;
```

**3. Seed the admin account**

```bash
php seed-admin.php
```

This creates the default admin user so you can log in straight away.

**4. Start the server**

```bash
php -S localhost:8000 public/index.php
```

Open [http://localhost:8000](http://localhost:8000) and you're in.

---

## Project structure

```
├── admin/          # Admin portal pages
├── api/            # JSON endpoints (notifications, messaging, availability)
├── auth/           # Login, register, forgot/reset password
├── config/         # App config, DB connection, constants
├── doctor/         # Doctor portal pages
├── emergency/      # Nearest facility search
├── includes/       # Shared layout (header, navbar, footer, functions)
├── patient/        # Patient portal pages
├── pharmacy/       # Pharmacy portal pages
├── public/         # Web root — index.php router + assets
│   └── assets/
│       ├── css/    # main, dashboard, auth, consultation, responsive
│       ├── js/     # main, notifications
│       └── img/    # icons (SVG set)
├── schema.sql      # Full database schema
└── seed-admin.php  # Creates the first admin user
```

---

## Default credentials

After running `seed-admin.php`:

| Field | Value |
|---|---|
| Email | admin@mediconnect.zm |
| Password | Admin@2026 |
| Role | Admin |

Change these before putting anything online.

---

## Known limitations

This is a university project, not production software. A few things to be aware of:

- The `SECRET_KEY` in `config.php` is hardcoded — you'd want this in an environment variable for a real deployment
- File uploads go into `/uploads` with no CDN or object storage
- There's no email verification flow yet — accounts are active immediately on registration
- Voice/video consultations are stubbed out in the UI but the WebRTC signalling isn't fully implemented

---

## Screenshots

_(Add screenshots here once the UI is finalised)_

---

Built by AmelCMM — Computer Science, Year 3.
