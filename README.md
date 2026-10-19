# Murang'a County Public E-Service System

A centralized web-based portal designed to digitize, streamline, and track public service delivery across Murang'a County. The platform provides county residents with an accessible channel to file service requests and track updates while offering administrative officers an efficient management dashboard.

## Features
- Citizen registration, login and logout
- Submit requests with service category, location, description and optional image
- Citizen dashboard with request status and reference number
- Admin dashboard with open/resolved analytics
- Admin management of requests, service categories and user accounts
- Super administrator creates and manages staff and user accounts
- Password reset links with one-hour expiry (requires working PHP mail/SMTP configuration)
- Water and sanitation issue selection, payment requests and manual M-Pesa code verification
- Request status workflow: Pending → In Progress → Resolved
- Search/filter requests, status history, CSRF protection, password hashing and prepared SQL statements
- Responsive interface and demo data seeding

## Requirements
- PHP 8.1+ with MySQLi and fileinfo extensions enabled (the MySQL server must be running)
- Apache/Nginx or PHP's built-in development server

## Quick start
1. Extract this folder into your web root, or open a terminal in this folder.
2. Ensure `storage/` and `uploads/requests/` are writable by the web server.
3. Run:
   ```bash
   php -S localhost:8000 -t public
   ```
4. Open `http://localhost:8000`.
5. Visit the site through Apache. Importing the SQL file is required before first run; the app seeds starter categories and licence rates into the imported schema.

## User login details admin and super admin
- Email: `admin@muranga.go.ke`
- Password: `Admin@12345`

-Email: `bowino@eservice.go.ke`
- Password: `Bowino@eservice.go.ke`
With this you be able to check explore all the funtionalilies of the system

Change this password immediately for any real deployment. Demo account is seeded automatically only when the database has no users.

## Deployment notes
- Point the web server document root to the `public/` directory.
- Use HTTPS in production.
- Change the demo administrator password and set secure session cookie options.
- Keep `storage/` outside the public document root (it is outside `public/` by default).
- Configure backups, upload limits, logging and access controls for production.
- This is a demonstration project, not an official Murang'a County Government system. Do not publish it as an official government service without authorization and security review.


## Water and sanitation payment workflow
1. A citizen chooses Water and Sanitation and selects a listed issue (meter damaged/not working, incorrect reading, leakage, no supply, low pressure, water quality, unexpected bill, burst pipe, sewer/drain issue, or Other).
2. County staff/admin reviews the request and, when a charge applies, creates a payment request with the verified amount and official payment instructions.
3. The citizen sees the amount in Water payments, pays using the official channel, then submits the M-Pesa transaction code.
4. An administrator compares the submitted code against the official transaction record and marks it Verified or Rejected.

**Important:** The application does not automatically connect to Safaricom M-Pesa. The admin verification is a manual record-keeping workflow. For automatic confirmation, integrate an authorized Safaricom Daraja API flow with credentials, callback endpoint validation, and reconciliation. **Recomenadtoions for the future developers**

## Password reset email
The reset form uses PHP `mail()`. Many local development environments do not send email until a mail transport is configured. Configure SMTP through your hosting provider or replace `mail()` with a maintained SMTP library before testing password reset end-to-end. Reset links are single-use and expire after 60 minutes.

The Functionalities of the system

### 👤 Citizen Portal
* **User Authentication:** Secure registration, login, session handling, and role management.
* **Service Requests:** Submit cases across categories (Water & Sanitation, Infrastructure, Enquiries) with automatically generated tracking codes (e.g., `MUR-261018-XXXX`).
* **Real-time Status Tracking:** Monitor request progress (`Pending`, `In Progress`, `Resolved`, `Rejected`).
* **County Announcements:** Access news, public notices, and official service updates.

### 🛡️ Administrative Portal
* **Request Management:** Review, filter, inspect, and update status on incoming citizen requests.
* **Audit & History Log:** Track status changes and log administrative action timestamps.
* **Content Management:** Create and edit public news announcements, notices, and service categories.

---

## 🛠️ The tools used

* **Backend:** PHP 8.x (Custom MVC/Modular structure)
* **Database:** MySQL / MariaDB via MySQLi wrapper (`DatabaseConnection`)
* **Frontend:** HTML5, CSS3, JavaScript
* **Server Environment:** Apache (XAMPP / WAMP / LAMP) this case we use XAMP


## News and public notices

Administrators can open **Admin Panel → News & Notices** to create, edit, publish, save drafts, attach images, and delete announcements. Supported categories include News, Public Notice, Service Update, Emergency Alert, and Event. Published announcements appear on the public **News & Notices** page and the home page. Drafts remain private to administrators. Images must be JPG, PNG, or WebP and no larger than 5 MB.

The interface uses Kenya flag-inspired black, red, green, and white accents. The portal remains a my project for ICT INTERSHIP ROLE  and should not be presented as an official county website.

## County services and business licences

After signing in, citizens see the county service directory on their dashboard. Water and Sanitation requests and payment requests are available through the existing request/payment workflow. The dedicated --Business licences-- page supports new business registration and renewal, national ID, phone, business name, Small/Medium/Large size, 1-, 3- or 7-year licence terms, and Murang'a sub-county, ward and manually entered local area. A renewal requires the current licence number. Applicants can submit an M-Pesa transaction code after the county approves the application; an administrator verifies or rejects the code in the admin panel.

Administrators can manage applications and configure the fee schedule at **Admin Panel → Business licences**. The initial fees in the database are illustrative only (Small: KSh 1,000 / 2,500 / 5,000; Medium: KSh 2,500 / 6,000 / 12,000; Large: KSh 5,000 / 12,000 / 25,000 for 1 / 3 / 7 years). These are **not verified or official Murang'a County charges**. These are my values just for the project.

### Database update note

All database tables are defined in `database/muranga_citizen.sql`. Import that file into the selected MySQL database before starting the app. For schema changes, back up the database and import the relevant migration or updated schema deliberately; the application does not attempt to create tables at runtime.


### Navigation and interface notes
- Internal links and redirects are base-path aware, so the portal can be installed either at the domain root (with `public/` as the document root) or in a subfolder such as `/muranga_public_service_portal/public/`.
- Bootstrap 5.3.3 and Font Awesome 6.7.2 are loaded from their official public CDN endpoints; an internet connection is needed for those visual assets. The portal's own stylesheet remains in `public/assets/css/style.css`.
- For production, configure the web server's document root to the project's `public/` directory and enable PHP MySQLi. Keep `app/`, `config/`, and `storage/` outside the publicly served directory.


## MySQL database setup (XAMPP / cPanel)

1. Start Apache and MySQL in XAMPP.
2. Open `http://localhost/phpmyadmin` and create a database named `muranga_citizen` using `utf8mb4_unicode_ci`.
3. Select that database, choose **Import**, and import `database/muranga_citizen.sql`.
4. Edit `config/config.php` and set `db_host`, `db_name`, `db_user`, and `db_password` for your MySQL account. XAMPP defaults are `127.0.0.1`, `muranga_citizen`, `root`, and an empty password.
5. Confirm PHP's `mysqli` extension is enabled, then open the site through Apache (for example `http://localhost/muranga_public_service_portal/public/`).

The schema file creates tables only; the application seeds the county service categories, editable demonstration business-licence rates, and initial super-admin account when needed. The admin: `admin@muranga.go.ke` / `Admin@12345`. Use the  this password immediately after first login. Licence fees are examples, not official county rates.

 📂 Project Structure

```text
muranga_public_service_portal/
├── app/
│   ├── bootstrap.php         # Application initialization & config loader
│   ├── mysql.php             # Database connection wrapper class
│   └── views/                # Reusable UI templates (header, footer)
├── public/
│   ├── index.php             # Main landing page
│   ├── request-view.php      # Individual request tracking view
│   ├── news.php              # Public news list
│   ├── uploads/              # Uploaded media assets
│   └── admin/                # Administrator panel controllers
│       ├── index.php         # Admin dashboard
│       └── news.php          # Announcement management
├── config.php                # Database and application configuration
└── README.md                 # Project documentation