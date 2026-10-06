# EventHub Deployment Guide

This guide explains how to publish EventHub on a PHP/MySQL web host so people
can open it from a public link without running the project on your laptop.

The steps use InfinityFree as an example of a free host. Hosting dashboards and
free-plan features can change, so follow the current instructions in the
provider's control panel. The host must support PHP, PDO MySQL, MySQL-compatible
databases, and a public URL.

## Before you begin

- Clone or download the project from
  [GitHub](https://github.com/krishptl0911-byte/Event-Booking-System) and keep
  a private copy on your computer.
- If you downloaded a ZIP, extract it before continuing.
- Have an email address available for the hosting account.
- Do not send anyone your hosting, FTP, or database passwords.
- Never use the site's simulated checkout to collect real payments.

The hosting package includes `database-hosting.sql`. It creates the site's
tables and sample events, but does not create/drop a database or include the
publicly known demo users and passwords.

## 1. Create the hosting account and public address

1. Open [InfinityFree](https://www.infinityfree.com/) or another PHP/MySQL host.
2. Sign up and verify your email if requested.
3. Sign in to its control panel.
4. Create a hosting account or website.
5. Choose an available free subdomain and finish the setup.
6. Save the resulting public URL. This is the address you will eventually share.

## 2. Create the database

1. In the hosting control panel, open **MySQL Databases** or the equivalent.
2. Create a database and database user/password if the host asks you to.
3. Copy the exact database connection details shown by the host:
   - Database host/server
   - Database name
   - Database username
   - Database password
4. Open **phpMyAdmin** from the hosting control panel.
5. Select the database you created.
6. Select **Import**.
7. Choose `database-hosting.sql` from the extracted project folder.
8. Start the import and check for a success message. The database should contain
   the `users`, `events`, `bookings`, and `payments` tables.

Do not import `database.sql` on the hosted database. That development file
contains commands to drop/create a database, which are usually not permitted
for a restricted hosting database user.

## 3. Configure the database connection

1. On your computer, open the project folder.
2. Copy `config/db.example.php` to `config/db.php` if `config/db.php` does not
   already exist. The local `config/db.php` is intentionally excluded from Git.
3. Open `config/db.php` in a text editor.
4. Replace the values assigned to `$host`, `$dbname`, `$user`, and `$pass` with
   the connection details from step 2. Use the exact database host provided;
   it may not be `localhost`.
5. Save the file.

Keep this configured file private. It contains the database password. Do not
post it publicly or commit it to a public repository.

## 4. Upload the website files

You can use the host's online File Manager if it supports ZIP extraction, or an
FTP client such as FileZilla Client. If you use a ZIP, create it from your local
project folder after configuring `config/db.php`; do not upload that ZIP to
GitHub.

### Using the host's File Manager

1. Create a ZIP of the configured project folder on your computer.
2. Open the File Manager in your hosting control panel.
3. Open the website's document root, often named `htdocs` or `public_html`.
4. Upload your ZIP file.
5. Use the File Manager's **Extract** option.
6. Check that `index.php` is directly inside the document root. If it is inside
   a nested `event-booking` directory, move the contents up into the document
   root so the public URL opens the homepage.

### Using FileZilla

1. Install FileZilla Client from [filezilla-project.org](https://filezilla-project.org/).
2. Find **FTP Accounts** or **FTP Details** in the hosting control panel.
3. In FileZilla, enter the FTP host, username, password, and port supplied by
   the host, then connect.
4. In the remote pane, open the website document root (often `htdocs` or
   `public_html`).
5. In the local pane, open the extracted project folder.
6. Upload the project files and folders into the document root. It should
   contain `index.php` and the `admin`, `assets`, `config`, and `includes`
   folders.
7. Upload the edited `config/db.php`, not the unconfigured original.

Do not put SQL exports, database backups, or other private files in a publicly
accessible folder. If the provider requires uploading the ZIP, remove it from
the document root after extraction.

## 5. Test the public site

1. Open the public URL in a new browser tab.
2. Confirm that EventHub's homepage and sample events are visible.
3. If the page shows a database connection error, recheck the four values in
   `config/db.php`, confirm the database exists, and confirm that the tables
   imported successfully.
4. If the host reports a missing PDO driver, check its PHP settings/control
   panel or ask its support whether PDO MySQL is enabled for your account.
5. Test registration and sign-in. The hosted database starts without user
   accounts, so register your own account first.

## 6. Make your account an administrator

Only do this for the account you control:

1. Register on your public website with your own email address.
2. Open phpMyAdmin from the hosting control panel.
3. Select the website database and open its **SQL** tab.
4. Replace the example email below with the address you registered, then run:

   ```sql
   UPDATE users SET role='admin' WHERE email='your-email@example.com';
   ```

5. Sign out and sign back in. You should now have access to the admin pages.

Do not use a demo password or share an administrator password with visitors.

## 7. Share the website

Once the public URL and sign-in work, send that URL to your friend. They only
need a browser; they do not need the project files, PHP, MySQL, or your laptop.

## Ongoing care

- Keep a private copy of the project and any database backups.
- Never share hosting-panel, FTP, or database credentials.
- Change your hosting account password if you accidentally expose it.
- Free plans can have storage, traffic, uptime, or activity limits. Check the
  provider's current terms and keep the site active as required.
- This project simulates checkout. It is not connected to a payment processor
  and must not be used to take real payments.
