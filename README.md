# MediCare HMS — Hospital Management System

A complete, deployment-ready Hospital Management System built with plain PHP, MySQL, HTML, CSS, and JavaScript — no frameworks required.

## Features

- **Role-based access**: Admin, Doctor, and Receptionist panels with separate permissions
- **Patient Management**: Register, search, edit, and view full patient profiles (history, lab results, bills, admissions in one place)
- **Doctor Management**: Profiles, specializations, schedules, consultation fees (Admin only)
- **Appointments**: Booking with double-booking prevention, status tracking (scheduled/completed/cancelled/no-show)
- **Wards & Beds**: Live bed occupancy across wards, admission and discharge workflows
- **Lab Tests**: Order tests from a catalog, record results
- **Pharmacy**: Medicine inventory with low-stock and expiry alerts; doctors write prescriptions that automatically deduct stock
- **Billing**: Itemized invoices with live total calculation, payment tracking, printable invoice view
- **Security**: Password hashing (bcrypt), CSRF protection on all forms, prepared statements (PDO) everywhere — no raw SQL concatenation

## Requirements

- XAMPP / WAMP / MAMP (or any PHP 7.4+ with MySQL/MariaDB)
- PHP extensions: `pdo_mysql` (included by default in XAMPP)

## Setup Instructions (XAMPP)

1. **Copy the project folder** into your XAMPP `htdocs` directory, so the path is:
   ```
   C:\xampp\htdocs\hms\   (Windows)
   /Applications/XAMPP/htdocs/hms/   (Mac)
   /opt/lampp/htdocs/hms/   (Linux)
   ```

2. **Start Apache and MySQL** from the XAMPP control panel.

3. **Import the database**:
   - Open `http://localhost/phpmyadmin`
   - Click **New** to create a database, or simply go to the **Import** tab
   - Import the file `database/hms_schema.sql` — this creates the `hms_db` database, all tables, and seed/demo data automatically.

4. **Check your configuration** in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hms_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');        // default XAMPP password is empty
   define('BASE_URL', 'http://localhost/hms'); // change if your folder name differs
   ```

5. **Visit the app**: `http://localhost/hms`

   You'll be redirected to the login page automatically.

## Demo Login Credentials

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `Admin@123` |
| Doctor | `drsmith` | `Admin@123` |
| Receptionist | `reception1` | `Admin@123` |

> New doctor accounts created via the Admin panel get the default password `Doctor@123`.
> New staff accounts (Admin/Receptionist) created via the Admin panel get the default password `Staff@123`.
> In a real deployment, prompt users to change their password on first login — this demo build does not include a "change password" flow, so add one before going live with real data.

## Project Structure

```
hms/
├── index.php                  # Entry point — redirects to login or dashboard
├── config/
│   └── database.php           # DB connection + BASE_URL config
├── includes/
│   ├── auth.php               # Session, role guards, CSRF, flash messages
│   ├── header.php / footer.php / sidebar.php / flash.php
├── auth/
│   ├── login.php
│   └── logout.php
├── admin/
│   ├── dashboard.php
│   └── users.php              # Manage admin/receptionist accounts
├── doctor/
│   └── dashboard.php
├── receptionist/
│   └── dashboard.php
├── modules/
│   ├── patients/   (list, add, edit, view)
│   ├── doctors/    (list, add, edit)
│   ├── appointments/ (list, add)
│   ├── wards/      (list, add_ward, admit)
│   ├── lab/        (list, add)
│   ├── pharmacy/   (list, add_medicine, prescribe)
│   └── billing/    (list, add, view)
├── assets/
│   ├── css/style.css
│   └── js/app.js
└── database/
    └── hms_schema.sql         # Full schema + seed data
```

## Who Can Access What

| Module | Admin | Doctor | Receptionist |
|---|---|---|---|
| Patients (view) | ✅ | ✅ | ✅ |
| Patients (add/edit) | ✅ | ❌ | ✅ |
| Doctors | ✅ full | view-only via list | view-only |
| Appointments | ✅ | view + status update (own only) | ✅ |
| Wards & Beds | ✅ | ❌ | ✅ |
| Lab Tests | ✅ | ✅ | ✅ |
| Pharmacy inventory | ✅ | view stock when prescribing | view-only |
| Prescriptions | — | ✅ | — |
| Billing | ✅ | view invoices | ✅ |
| Staff Accounts | ✅ | ❌ | ❌ |

## Notes on the Database Schema

- `users` holds login accounts for all three roles; `doctors` extends it with professional details for role = `doctor`.
- `patients` are independent of `users` — they don't log in (no patient portal in this build).
- `beds.status` and `admissions.status` are kept in sync by the admit/discharge actions — always go through the UI rather than editing these tables directly, or they'll drift out of sync.
- `medicines.stock_qty` is automatically decremented when a doctor saves a prescription with that medicine.
- Money fields use `DECIMAL(10,2)` throughout — never `FLOAT` — to avoid rounding errors in billing.

## Extending This Project

Some natural next additions if you want to keep building:
- A "change password" page (currently default passwords are shared via the admin)
- A patient-facing portal (would need a `patients` login flow, separate from `users`)
- Email/SMS notifications for appointment reminders
- PDF export for invoices (currently uses browser print-to-PDF via the Print button)
- Audit logging for sensitive actions (discharges, billing edits)

---

Built with plain PHP + MySQL (PDO) for maximum compatibility with XAMPP/WAMP setups — no Composer, no build step, just copy and import.
