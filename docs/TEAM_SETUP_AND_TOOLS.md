# CampusGuard Setup Tutorial and Tools Used

This document explains how to pull Joseph's branch, configure secrets, import the database, run the PHP website, optionally run the FastAPI backend bridge, optionally run Ollama, and submit a test incident.

## 1. Branch to Use

Use the full branch:

```bash
git checkout feature/joseph-fastapi-backend-bridge
git pull
2. What Was Added

Joseph added:

Clean login page.
Student incident reporting improvements.
Student report helper widget.
Staff dashboards for institutional roles.
Privacy-safe separation between lecturer, finance, dean, and admin views.
Admin assignment to responsible staff, not only technicians.
PHP routing and analysis layer.
Optional FastAPI backend bridge.
Optional local AI helper using Ollama.
Compliance ledger and audit support.
Backend bridge advisory table.
Team setup documentation.
3. Main Project Structure
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
4. Tools Used
Git

Used for branching, tracking changes, committing, pulling, pushing, and comparing work.

git status
git branch --show-current
git log --oneline
git fetch origin
git checkout feature/joseph-fastapi-backend-bridge
git pull
git push
GitHub

Used for the group repository, branch review, pull requests, and sharing project work.

PHP

Used for the main web application:

Login.
Registration.
Student reporting.
Admin dashboard.
Staff dashboards.
Notifications.
MySQL/MariaDB interaction.

Run:

php -S localhost:8080
MySQL or MariaDB

Used by the PHP application.

Database: campus_guard
Schema: database.sql
Python

Used for the optional backend service.

FastAPI

Used for the optional backend bridge.

POST /bridge/analyze
Uvicorn

Used to run the FastAPI backend.

uvicorn app.main:app --reload --host 127.0.0.1 --port 8000
Ollama

Used for optional local AI support.

ollama pull llama3.2:1b

The main PHP system still works without Ollama.

WSL Ubuntu

Used as the Linux development terminal.

Browser

Used to test the web system.

http://localhost:8080/
http://localhost:8080/login.php
http://127.0.0.1:8000/docs
5. Where Secrets Are Stored
PHP database secrets

The PHP application reads database settings from environment variables.

Required:

CAMPUSGUARD_DB_PASS

Optional:

CAMPUSGUARD_DB_HOST
CAMPUSGUARD_DB_USER
CAMPUSGUARD_DB_NAME

For local demo:

export CAMPUSGUARD_DB_HOST='127.0.0.1'
export CAMPUSGUARD_DB_USER='campusguard_user'
export CAMPUSGUARD_DB_NAME='campus_guard'
export CAMPUSGUARD_DB_PASS='CampusGuardLocal123!'

Then run:

php -S localhost:8080
FastAPI backend secrets

The backend uses:

backend/.env

This file stays local and must not be committed.

Safe template:

backend/.env.example

Prepare backend environment:

cd backend
cp .env.example .env
nano .env
GitHub credentials

Do not store GitHub passwords or personal access tokens in this repository.

Ollama

Ollama local use does not require a paid API key.

6. Setup From GitHub

Clone:

git clone https://github.com/doristeyagirwa-boop/Campus-incident-reporting.git
cd Campus-incident-reporting

Fetch and checkout:

git fetch origin
git checkout feature/joseph-fastapi-backend-bridge
git pull
7. Database Setup

Open MySQL or MariaDB as root:

mysql -u root -p

Create database and user:

CREATE DATABASE IF NOT EXISTS campus_guard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'campusguard_user'@'127.0.0.1'
IDENTIFIED BY 'CampusGuardLocal123!';

GRANT ALL PRIVILEGES ON campus_guard.* TO 'campusguard_user'@'127.0.0.1';

FLUSH PRIVILEGES;

EXIT;

Import schema:

mysql -h 127.0.0.1 -u campusguard_user -p campus_guard < database.sql
8. Run the PHP App

From the project root:

export CAMPUSGUARD_DB_HOST='127.0.0.1'
export CAMPUSGUARD_DB_USER='campusguard_user'
export CAMPUSGUARD_DB_NAME='campus_guard'
export CAMPUSGUARD_DB_PASS='CampusGuardLocal123!'

php -S localhost:8080

Open:

http://localhost:8080/
http://localhost:8080/login.php
9. Demo Accounts
Student:      190439@student.school.ac.ke / Password1!
Admin:        admin@school.ac.ke / Password1!
Technician:   technician@school.ac.ke / Password1!
Lecturer:     lecturer.web@school.ac.ke / Password1!
Registrar:    registrar@school.ac.ke / Password1!
Dean:         dean@school.ac.ke / Password1!
Finance:      finance@school.ac.ke / Password1!
Kitchen:      kitchen.main@school.ac.ke / Password1!
Cafe:         cafe.two@school.ac.ke / Password1!
Security:     security@school.ac.ke / Password1!
Maintenance:  maintenance@school.ac.ke / Password1!
10. Submit an Incident

Login as:

190439@student.school.ac.ke / Password1!

Open:

http://localhost:8080/student/report.php

Submit:

Title: Fee clearance not reflected
Priority: Medium
Location: Accounts office
Description: Student has paid but fee balance still blocks exam card.

Expected:

Incident is saved.
Student is redirected to incident view.
Student receives confirmation.
PHP routing processes the report.
If FastAPI is running, a backend advisory is saved.
If FastAPI is offline, the report still works.
11. Optional FastAPI Backend

Open a second terminal:

cd backend
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000

Open:

http://127.0.0.1:8000/docs

Health check:

curl -s http://127.0.0.1:8000/system/health

Bridge test:

curl -s -X POST http://127.0.0.1:8000/bridge/analyze \
  -H "Content-Type: application/json" \
  -d '{
    "title":"Fee clearance not reflected",
    "description":"Student has paid but fee balance still blocks exam card",
    "category":"Finance",
    "location":"Accounts office"
  }'

Expected response includes:

domain
priority_hint
risk_hint
recommended_action
confidence
matched_terms
12. Optional Ollama

Check:

ollama --version
ollama list

Install local model if needed:

ollama pull llama3.2:1b

Test:

ollama run llama3.2:1b "Classify this campus incident: WiFi is down in LT4."

Ollama is optional. If it is offline, the PHP app and FastAPI bridge still work.

13. Verify PHP to FastAPI Bridge

After submitting a report while FastAPI is running:

mysql -h 127.0.0.1 -u campusguard_user -p campus_guard -e "
SELECT advisory_id, incident_id, domain_hint, priority_hint, risk_hint, confidence, recommended_action
FROM backend_bridge_advisories
ORDER BY advisory_id DESC
LIMIT 10;
"

Rows mean PHP successfully called FastAPI.

14. Admin Assignment Demo

Login as:

admin@school.ac.ke / Password1!

Open:

http://localhost:8080/admin/incidents.php

Open an incident, use:

Assign Responsible Person

Choose a staff member and save.

Expected:

Incident assignment updates.
Assigned staff gets a notification.
Student sees that a responsible office has been assigned.
Audit log records the update.
15. Privacy Checks

Finance should show:

Fee clearance
Balance
Finance status

Finance should not show:

Grades
Attendance percentage

Lecturer should show:

Attendance
Grade
Exam readiness

Lecturer should not show:

Fee balance

Dean should show:

Risk summary
16. Troubleshooting

If PHP cannot connect to database:

echo $CAMPUSGUARD_DB_PASS
export CAMPUSGUARD_DB_PASS='CampusGuardLocal123!'
php -S localhost:8080

If FastAPI does not start:

cd backend
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000

If bridge endpoint returns 404:

curl -s http://127.0.0.1:8000/openapi.json | grep -o "/bridge/analyze"

If Ollama does not respond:

ollama serve

Then in another terminal:

ollama list

PHP syntax check:

php -l login.php
php -l student/report.php
php -l admin/view_incident.php
php -l includes/backend_bridge.php
17. Cost

Local demo costs zero when using:

Local PHP server.
Local MySQL or MariaDB.
Local FastAPI.
Local Ollama.
No paid SMS provider.
No paid email provider.
No paid AI API provider.
18. Security Reminder

Do not commit:

.env
backend/.env
backend/.venv/
backend/venv/
private keys
GitHub tokens
real production database passwords

Only commit:

.env.example
safe documentation
source code
database schema
