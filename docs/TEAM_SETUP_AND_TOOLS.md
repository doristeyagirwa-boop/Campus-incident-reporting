# CampusGuard Setup Tutorial and Tools Used

This guide explains how team members can pull Joseph's branch, configure secrets, import the database, run the PHP website, optionally run the FastAPI backend bridge, optionally run Ollama, and submit a test incident.

---

## 1. Branch to Use

Use Joseph's full branch:

```bash
git fetch origin
git checkout feature/joseph-fastapi-backend-bridge
git pull
```

---

## 2. What Joseph Added

Joseph added the following:

- Clean login page.
- Student incident reporting improvements.
- Student report helper widget.
- Staff dashboards for institutional roles.
- Privacy-safe separation between lecturer, finance, dean, and admin views.
- Admin assignment to responsible staff, not only technicians.
- PHP routing and analysis layer.
- Optional FastAPI backend bridge.
- Optional local AI helper using Ollama.
- Compliance ledger and audit support.
- Backend bridge advisory table.
- Team setup documentation.

---

## 3. Main Project Structure

```text
Campus-incident-reporting/
├── admin/
├── student/
├── technician/
├── institutional/
├── includes/
├── css/
├── js/
├── backend/
├── docs/
├── database.sql
├── index.php
├── login.php
└── register.php
```

---

## 4. Tools Used

### Git

Used for branching, tracking changes, committing, pulling, pushing, and comparing work.

```bash
git status
git branch --show-current
git log --oneline
git fetch origin
git checkout feature/joseph-fastapi-backend-bridge
git pull
git push
```

### GitHub

Used for:

- Group repository hosting.
- Branch review.
- Pull requests.
- Team collaboration.
- Sharing project work.

### PHP

Used for the main web application.

The PHP side handles:

- Login.
- Registration.
- Student reporting.
- Student dashboard.
- Admin dashboard.
- Staff dashboards.
- Notifications.
- MySQL/MariaDB interaction.
- Local routing and analysis logic.

Run the PHP website with:

```bash
php -S localhost:8080
```

### MySQL or MariaDB

Used by the PHP application.

```text
Database: campus_guard
Schema file: database.sql
```

### Python

Used for the optional backend service inside the `backend/` folder.

### FastAPI

Used for the optional backend bridge.

Main route added:

```text
POST /bridge/analyze
```

This route can analyze an incident and return:

```text
domain
priority_hint
risk_hint
recommended_action
confidence
matched_terms
```

### Uvicorn

Used to run the FastAPI backend.

```bash
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000
```

### Ollama

Used for optional local AI support.

Recommended model:

```bash
ollama pull llama3.2:1b
```

The main PHP website still works without Ollama.

### WSL Ubuntu

Used as the Linux development terminal environment.

### Browser

Used to test the website and backend documentation.

Useful URLs:

```text
http://localhost:8080/
http://localhost:8080/login.php
http://127.0.0.1:8000/docs
```

---

## 5. Where Secrets Are Stored

### PHP Database Secrets

The PHP application reads database settings from environment variables.

Required variable:

```bash
CAMPUSGUARD_DB_PASS
```

Optional variables:

```bash
CAMPUSGUARD_DB_HOST
CAMPUSGUARD_DB_USER
CAMPUSGUARD_DB_NAME
```

For local demo, run this before starting PHP:

```bash
export CAMPUSGUARD_DB_HOST='127.0.0.1'
export CAMPUSGUARD_DB_USER='campusguard_user'
export CAMPUSGUARD_DB_NAME='campus_guard'
export CAMPUSGUARD_DB_PASS='CampusGuardLocal123!'
```

Then run:

```bash
php -S localhost:8080
```

### FastAPI Backend Secrets

The backend uses a local `.env` file:

```text
backend/.env
```

This file should stay on your machine and must not be committed.

The safe template is:

```text
backend/.env.example
```

To prepare backend secrets:

```bash
cd backend
cp .env.example .env
nano .env
```

### GitHub Credentials

Do not store GitHub passwords, personal access tokens, or private keys in this repository.

### Ollama

Ollama local use does not require a paid API key.

---

## 6. Setup From GitHub

Clone the repository:

```bash
git clone https://github.com/doristeyagirwa-boop/Campus-incident-reporting.git
cd Campus-incident-reporting
```

Fetch branches:

```bash
git fetch origin
```

Checkout Joseph's branch:

```bash
git checkout feature/joseph-fastapi-backend-bridge
```

Pull latest changes:

```bash
git pull
```

---

## 7. Database Setup

Open MySQL or MariaDB as root:

```bash
mysql -u root -p
```

Create the database and user:

```sql
CREATE DATABASE IF NOT EXISTS campus_guard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'campusguard_user'@'127.0.0.1'
IDENTIFIED BY 'CampusGuardLocal123!';

GRANT ALL PRIVILEGES ON campus_guard.* TO 'campusguard_user'@'127.0.0.1';

FLUSH PRIVILEGES;

EXIT;
```

Import the database schema:

```bash
mysql -h 127.0.0.1 -u campusguard_user -p campus_guard < database.sql
```

When asked for the database password, use:

```text
CampusGuardLocal123!
```

---

## 8. Run the PHP Website

From the project root:

```bash
export CAMPUSGUARD_DB_HOST='127.0.0.1'
export CAMPUSGUARD_DB_USER='campusguard_user'
export CAMPUSGUARD_DB_NAME='campus_guard'
export CAMPUSGUARD_DB_PASS='CampusGuardLocal123!'

php -S localhost:8080
```

Open:

```text
http://localhost:8080/
```

Login page:

```text
http://localhost:8080/login.php
```

---

## 9. Demo Accounts

| Role | Email | Password |
|---|---|---|
| Student | 190439@student.school.ac.ke | Password1! |
| Admin | admin@school.ac.ke | Password1! |
| Technician | technician@school.ac.ke | Password1! |
| Lecturer | lecturer.web@school.ac.ke | Password1! |
| Registrar | registrar@school.ac.ke | Password1! |
| Dean | dean@school.ac.ke | Password1! |
| Finance | finance@school.ac.ke | Password1! |
| Kitchen | kitchen.main@school.ac.ke | Password1! |
| Cafe | cafe.two@school.ac.ke | Password1! |
| Security | security@school.ac.ke | Password1! |
| Maintenance | maintenance@school.ac.ke | Password1! |

---

## 10. Submit a Test Incident

Login as the student:

```text
190439@student.school.ac.ke / Password1!
```

Open:

```text
http://localhost:8080/student/report.php
```

Submit this incident:

```text
Title: Fee clearance not reflected
Priority: Medium
Location: Accounts office
Description: Student has paid but fee balance still blocks exam card.
```

Expected result:

- Incident is saved.
- Student is redirected to the incident view page.
- Student receives confirmation.
- PHP routing processes the report.
- If FastAPI is running, a backend advisory is saved.
- If FastAPI is offline, the report still works.

---

## 11. Optional FastAPI Backend

The PHP website does not require FastAPI. This is only for the advanced demo.

Open a second terminal:

```bash
cd backend
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000
```

Open:

```text
http://127.0.0.1:8000/docs
```

Health check:

```bash
curl -s http://127.0.0.1:8000/system/health
```

Bridge test:

```bash
curl -s -X POST http://127.0.0.1:8000/bridge/analyze \
  -H "Content-Type: application/json" \
  -d '{
    "title":"Fee clearance not reflected",
    "description":"Student has paid but fee balance still blocks exam card",
    "category":"Finance",
    "location":"Accounts office"
  }'
```

Expected response includes:

```text
domain
priority_hint
risk_hint
recommended_action
confidence
matched_terms
```

---

## 12. Optional Ollama

Check Ollama:

```bash
ollama --version
ollama list
```

Install the local model if missing:

```bash
ollama pull llama3.2:1b
```

Test Ollama:

```bash
ollama run llama3.2:1b "Classify this campus incident: WiFi is down in LT4."
```

Ollama is optional. If it is offline, the PHP website and FastAPI bridge still work.

---

## 13. Verify PHP to FastAPI Bridge

Submit a report while FastAPI is running.

Then run:

```bash
mysql -h 127.0.0.1 -u campusguard_user -p campus_guard -e "
SELECT advisory_id, incident_id, domain_hint, priority_hint, risk_hint, confidence, recommended_action
FROM backend_bridge_advisories
ORDER BY advisory_id DESC
LIMIT 10;
"
```

Rows in `backend_bridge_advisories` mean PHP successfully called the FastAPI backend.

---

## 14. Admin Assignment Demo

Login as admin:

```text
admin@school.ac.ke / Password1!
```

Open:

```text
http://localhost:8080/admin/incidents.php
```

Then:

```text
Open an incident.
Use Assign Responsible Person.
Choose a staff member.
Save the update.
```

Expected result:

- Incident assignment updates.
- Assigned staff gets a notification.
- Student sees that a responsible office has been assigned.
- Audit log records the update.

---

## 15. Privacy Checks

Finance dashboard should show:

```text
Fee clearance
Balance
Finance status
```

Finance dashboard should not show:

```text
Grades
Attendance percentage
```

Lecturer dashboard should show:

```text
Attendance
Grade
Exam readiness
```

Lecturer dashboard should not show:

```text
Fee balance
```

Dean dashboard should show:

```text
Risk summary
```

Dean dashboard should not expose unnecessary detailed finance data.

---

## 16. Troubleshooting

If PHP cannot connect to the database, confirm the password variable:

```bash
echo $CAMPUSGUARD_DB_PASS
```

Then rerun:

```bash
export CAMPUSGUARD_DB_PASS='CampusGuardLocal123!'
php -S localhost:8080
```

If FastAPI does not start:

```bash
cd backend
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000
```

If the bridge endpoint returns 404:

```bash
curl -s http://127.0.0.1:8000/openapi.json | grep -o "/bridge/analyze"
```

If Ollama does not respond:

```bash
ollama serve
```

Then in another terminal:

```bash
ollama list
```

If PHP syntax needs checking:

```bash
php -l login.php
php -l student/report.php
php -l admin/view_incident.php
php -l includes/backend_bridge.php
```

---

## 17. Cost

The local demo costs zero when using:

- Local PHP server.
- Local MySQL or MariaDB.
- Local FastAPI.
- Local Ollama.
- No paid SMS provider.
- No paid email provider.
- No paid AI API provider.

---

## 18. Security Reminder

Do not commit:

```text
.env
backend/.env
backend/.venv/
backend/venv/
private keys
GitHub tokens
real production database passwords
```

Only commit:

```text
.env.example
safe documentation
source code
database schema
```

---

## 19. Final Quick Test Checklist

Run PHP:

```bash
export CAMPUSGUARD_DB_HOST='127.0.0.1'
export CAMPUSGUARD_DB_USER='campusguard_user'
export CAMPUSGUARD_DB_NAME='campus_guard'
export CAMPUSGUARD_DB_PASS='CampusGuardLocal123!'
php -S localhost:8080
```

Open:

```text
http://localhost:8080/login.php
```

Login as student:

```text
190439@student.school.ac.ke / Password1!
```

Submit a report.

Then login as admin:

```text
admin@school.ac.ke / Password1!
```

Open the report and assign a responsible person.

If all this works, the main demo is ready.
