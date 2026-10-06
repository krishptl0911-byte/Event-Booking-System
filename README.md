# EventHub - Online Event Booking System

A PHP and MySQL event-booking website with event discovery, seat booking,
simulated checkout, printable QR tickets, and an admin check-in dashboard.
Built with HTML, CSS, JavaScript, PHP (PDO), and MySQL; no Composer or npm
packages are required.

---

## Run locally

### Requirements

- PHP with the PDO MySQL extension enabled
- MySQL or MariaDB
- Git to clone the project
- A browser

XAMPP or a similar PHP/MySQL bundle is the easiest option on Windows. Apache
serves the website and MySQL stores its data. The built-in PHP server below can
replace Apache, but it does not replace MySQL.

### 1. Clone the repository

In PowerShell, run:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/krishptl0911-byte/Event-Booking-System.git
cd Event-Booking-System
Copy-Item config\db.example.php config\db.php
```

If you are not using XAMPP, clone the repository into your preferred web
directory and use that directory in place of `C:\xampp\htdocs`.

### 2. Start MySQL and create the sample database

Start MySQL in the XAMPP Control Panel. Open `http://localhost/phpmyadmin/`,
choose **Import**, select `database.sql` from the cloned folder, and click
**Import** or **Go**.

> **Warning:** `database.sql` drops and recreates the `event_booking` database.
> Import it only into a local development database whose existing data you are
> willing to replace. It adds sample events and demo accounts.

### 3. Configure the database connection

The copied `config/db.php` uses the default XAMPP settings: host `localhost`,
database `event_booking`, user `root`, and an empty password. If your local
MySQL uses different values, edit `config/db.php` and set the correct host,
database, username, and password. This local file is ignored by Git; do not
commit real database credentials.

### 4. Open the website

With XAMPP, make sure Apache is running and visit
`http://localhost/Event-Booking-System/`.

Alternatively, from the project folder run:

```powershell
php -S localhost:8000
```

Then visit `http://localhost:8000/`. MySQL must still be running, and PHP must
have PDO MySQL enabled.

### Demo accounts

The local `database.sql` seed data includes the accounts below. They are for
local testing only; never use these passwords on a public deployment.

| Role | Email | Password |
|---|---|---|
| Admin | `admin@events.com` | `admin123` |
| User | `riya@example.com` | `user123` |
| User | `amit@example.com` | `user123` |

### Method 3: Put the Website Online (Share a Link)

To let someone visit the site without running anything on your laptop, upload it
to a web host that supports PHP and MySQL. The host runs the website; your
friend only opens the public URL it gives you, and your laptop does not need to
stay on.

See [DEPLOYMENT-GUIDE.md](./DEPLOYMENT-GUIDE.md) for the full step-by-step
hosting instructions.

1. Create an account with a PHP/MySQL web host and create a free subdomain.
   Check that the plan includes PHP PDO MySQL and a MySQL-compatible database.
2. Create a database and database user in the host's control panel. In the
   host's phpMyAdmin, select that database and import `database-hosting.sql`.
   Unlike `database.sql`, this hosting version does not try to create or drop
   a database.
3. Upload the project files and folders to the host's web root (often named
   `htdocs` or `public_html`). Upload the contents of this folder, including
   `index.php`, `config`, `includes`, `assets`, and `admin`.
4. Create `config/db.php` on the host from `config/db.example.php`, then set
   `$host`, `$dbname`, `$user`, and `$pass` to the database connection details
   shown in the hosting panel. Do not publish those credentials or commit them
   to a public repository.
5. Open the host's URL and test the homepage and sign-in. Share that URL with
   your friend; they do not need the project files or PHP/MySQL installed.

The hosting SQL file adds sample events but deliberately does not add the
publicly known demo users or passwords. After uploading, register your own
account, then use phpMyAdmin's SQL tab to promote only that account to admin:
`UPDATE users SET role='admin' WHERE email='your-email@example.com';`
Replace the email with the address you registered. Do this before sharing the
site URL.

Free hosts may have usage limits or suspend inactive sites. Keep a private copy
of the project and database credentials. Never use the demo accounts for real
customer data.

---

## 🔑 Demo Logins

| Role | Email | Password | Access / Capabilities |
|---|---|---|---|
| **Admin (local demo only)** | `admin@events.com` | `admin123` | Dashboard metrics, Add/Edit/Delete events, Verify tickets & Gate Check-in |
| **User (local demo only)** | `riya@example.com` | `user123` | Browse events, Book seats, Checkout, View & Print QR E-Tickets, Cancel |
| **User (local demo only)** | `amit@example.com` | `user123` | Additional demo account with sample booking history |

---

## 📂 Project Structure & File Roles

```
event-booking/
├── database.sql               MySQL relational schema (4 tables) + seed data + viva queries
├── config/
│   ├── db.example.php         PDO connection template; copy to db.php locally
│   └── db.php                 Local-only database settings (ignored by Git)
├── includes/
│   ├── init.php               Central bootstrap: session, PDO, BASE_URL auto-detect, helpers
│   ├── header.php             Responsive navigation bar, user session badge, flash alerts
│   └── footer.php             Shared footer & script inclusion
├── index.php                  Homepage: event catalog, keyword search, category filter
├── event.php                  Event detail view, live seat status, dynamic total price calculation
├── book.php                   Core booking engine with ACID transaction + FOR UPDATE row lock
├── checkout.php               Simulated payment gateway (UPI/Card/NetBanking) + 10-min countdown
├── ticket.php                 Printable e-ticket with attendee details & HTML5 canvas QR code
├── my_bookings.php            User dashboard: booking history, pay-now link, cancellation
├── cancel.php                 Cancellation processor: returns seats to inventory & marks refund
├── register.php               New user registration (client & server-side validation, bcrypt)
├── login.php                  Authentication with password_verify() & session fixation guard
├── logout.php                 Destroys session and redirects safely
├── admin/
│   ├── index.php              Admin dashboard: aggregate KPI metrics, events table, bookings log
│   ├── event_form.php         Unified Add & Edit event form with seat-reduction validation
│   ├── event_delete.php       Foreign-key safe event deletion (blocks deleting booked events)
│   └── verify.php             Gatekeeper ticket verification & live check-in scanner
└── assets/
    ├── css/
    │   └── style.css          Complete custom styling (Flexbox, CSS Grid, Badges, Print mode)
    └── js/
        ├── script.js          DOM handlers (live ticket multiplier, 10-min timer, mobile menu)
        └── qr.js              Custom QR engine (Version 1, 21x21, Reed-Solomon GF(256) ECC)
```

---

## 🔄 End-to-End Application Workflow

1. **Discovery:** Attendee searches events by keyword or category filter on `index.php`.
2. **Quantity Selection:** Attendee selects ticket quantity on `event.php`. JavaScript updates the total price in real time.
3. **Seat Locking (Atomic Transaction):**
   - Attendee clicks "Book now".
   - `book.php` initiates a MySQL transaction with `SELECT ... FOR UPDATE` on the event row.
   - It verifies that sufficient seats remain and decrements `available_seats`.
   - Inserts booking with status `pending` and a unique 12-character code (e.g. `EVT-A1B2C3D4`).
4. **Checkout & Expiry Guard:**
   - Attendee is redirected to `checkout.php` with a 10-minute countdown timer.
   - If not paid within 10 minutes, `expirePendingBookings()` automatically cancels the booking and restores seats to inventory.
   - Upon simulated payment (UPI, Card, Net Banking), booking transitions to `confirmed`.
5. **E-Ticket & QR Generation:**
   - Attendee views printable ticket on `ticket.php`.
   - `qr.js` executes mathematical Galois Field $\text{GF}(256)$ polynomials to render a scannable QR code on an HTML5 `<canvas>`.
6. **Gate Entry & Verification:**
   - At the venue entrance, the gatekeeper / admin opens `admin/verify.php`.
   - Admin enters or scans the booking code.
   - System checks status in MySQL:
     - ❌ **Invalid / Cancelled / Unpaid:** Rejects entry.
     - ⚠️ **Already Used:** Alerts gatekeeper that the ticket was already checked in.
     - ✅ **Valid Ticket:** Displays attendee details, approved seat count, and allows one-click check-in.

---

## 🛡️ Security Mechanisms

- **SQL Injection Prevention:** 100% of database queries use PDO prepared statements with parameter binding (`?` placeholders).
- **Cross-Site Scripting (XSS):** Every dynamic output is sanitized using `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- **Password Security:** Salted hashing using PHP's native `password_hash($pwd, PASSWORD_DEFAULT)` (Bcrypt) and `password_verify()`.
- **Session Fixation Prevention:** Calls `session_regenerate_id(true)` immediately upon successful login.
- **Race Condition Prevention:** Database row-level locking (`FOR UPDATE`) inside `PDO::beginTransaction()` prevents double-booking when multiple users book the last seat simultaneously.
- **Open Redirect Protection:** Login redirects are strictly validated against a safe regex whitelist (`/^[a-z_]+\.php(\?id=\d+)?$/`).

---
