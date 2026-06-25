# CampusGuard — Campus Incident Reporting System

A full-stack PHP/MySQL web application for reporting and tracking campus incidents (network, security, infrastructure, academic).

**Web Programming Final Project — CNS 2106**

---

## Demo Accounts

| Role      | Email                        | Password    |
|-----------|------------------------------|-------------|
| Admin     | admin@campusguard.ac.ke      | Password1!  |
| Student   | jane@campusguard.ac.ke       | Password1!  |
| Technician| daniel@campusguard.ac.ke     | Password1!  |

---

## Setup (XAMPP / WAMP)

### 1. Clone the repo
```bash
git clone https://github.com/YOUR_USERNAME/campusguard.git
```
Place the folder inside `htdocs/` (XAMPP) or `www/` (WAMP).

### 2. Import the database
- Open **phpMyAdmin** → create a database named `campus_guard`
- Click **Import** → select `database.sql` → click **Go**

Or via terminal:
```bash
mysql -u root -p < database.sql
```

### 3. Configure the app
Open `includes/config.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'campus_guard');
define('DB_USER', 'root');
define('DB_PASS', '');          // your MySQL password if set
define('SITE_URL', 'http://localhost/campusguard');
```

### 4. Make uploads writable
```bash
chmod 775 uploads/
```
On Windows (XAMPP) this is not needed.

### 5. Visit the site
```
http://localhost/campusguard
```

---

## Project Structure

```
campusguard/
├── includes/
│   ├── config.php          ← only file you need to change
│   ├── db.php              ← database connection
│   ├── auth.php            ← session, login, role helpers
│   ├── header_student.php
│   ├── footer_student.php
│   ├── header_admin.php
│   └── footer_admin.php
│
├── css/
│   └── style.css
│
├── js/
│   └── app.js
│
├── uploads/                ← incident attachments (git-ignored)
│
├── student/
│   ├── dashboard.php
│   ├── report.php
│   ├── my_reports.php
│   ├── view_incident.php
│   ├── notifications.php
│   └── profile.php
│
├── admin/
│   ├── dashboard.php       ← stats + chart
│   ├── incidents.php       ← manage all incidents
│   ├── view_incident.php   ← update status/priority/assign
│   └── users.php           ← manage users + roles
│
├── index.php               ← public homepage
├── login.php
├── register.php
├── logout.php
└── database.sql            ← import this first
```

---

## Features

- **Authentication** — register, login, logout, bcrypt passwords, session security
- **Role-based access** — student, technician, admin; pages redirect if wrong role
- **Incident reporting** — title, category, priority, location, description, file attachment
- **Status tracking** — Open → Assigned → In Progress → Resolved → Closed
- **Comments** — threaded per incident, visible to reporter and admin
- **Notifications** — in-app alerts when status changes
- **Admin dashboard** — system stats + doughnut chart by category
- **Admin incident management** — filter, search, assign technician, change status/priority
- **Admin user management** — change roles, delete users
- **Student profile** — update name, change password
- **Responsive** — works on mobile and desktop

---

## Tech Stack

| Layer    | Technology            |
|----------|-----------------------|
| Frontend | HTML5, CSS3, Vanilla JS |
| Backend  | PHP 8                 |
| Database | MySQL (via mysqli)    |
| Server   | Apache (XAMPP/WAMP)   |
| Charts   | Chart.js (CDN)        |

---

## Git Workflow (team of 5)

```
main
├── auth-module          ← Member 1
├── incident-module      ← Member 2
├── student-dashboard    ← Member 3
├── admin-module         ← Member 4
└── reporting-module     ← Member 5
```

**Never commit directly to `main`.** Open a pull request from your branch.

---

## Security

- Passwords hashed with `password_hash()` (bcrypt)
- All DB queries use prepared statements (no SQL injection)
- `session_regenerate_id(true)` on login (prevents session fixation)
- File uploads: extension whitelist + size limit
- Role checks on every protected page
