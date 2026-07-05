# Incident Reporting Module

This backend module was contributed by Joseph Oigara Ogendi as part of the Campus Incident Reporting System group project.

## Overview

This module provides the backend foundation for reporting, tracking, managing, and auditing campus incidents. It focuses on the main server-side functionality needed for incident creation, authentication, protected access, database storage, comments, history, and dashboard metrics.

## What This Module Includes

- Incident creation
- Incident listing
- Incident detail viewing
- Incident status updates
- Incident comments
- Incident history
- Attachment support foundation
- Audit logging foundation
- Priority and risk scoring
- SLA response and resolution deadline support
- Metrics dashboard endpoint
- Notification and event architecture foundation

## Authentication and Security

The backend includes authentication and access control features such as:

- User registration
- User login
- Institution ID, email, and password login
- JWT access tokens
- Refresh tokens
- Refresh token rotation
- Logout support
- Current user endpoint
- Login sessions
- Login attempts
- Role-based access guard
- Protected incident endpoints

## Database Work

The project uses PostgreSQL for data storage and SQLAlchemy for database models.

The backend includes database support for:

- Users
- Roles
- Permissions foundation
- Sessions
- Refresh tokens
- Login sessions
- Login attempts
- Password reset foundation
- Email verification foundation
- Incidents
- Incident history
- Incident comments
- Attachments
- Audit logs

## Main API Areas

The backend includes or prepares the following API areas:

- /system/health
- /auth/register
- /auth/login
- /auth/refresh
- /auth/logout
- /auth/me
- /incidents
- /incidents/{id}
- /incidents/{id}/status
- /incidents/{id}/comments
- /incidents/metrics/dashboard

## Technology Used

- Python
- FastAPI
- PostgreSQL
- SQLAlchemy
- Alembic
- Pydantic
- JWT authentication
- Refresh tokens
- Argon2 password hashing
- Role-Based Access Control
- Uvicorn
- Git
- GitHub
- Windows 11
- WSL Ubuntu

## Why These Tools Were Used

Python was used because it is readable, reliable, and works well for backend APIs.

FastAPI was used to build the API routes because it is fast, modern, and provides automatic API documentation.

PostgreSQL was used as the main database because it is stable, production-ready, and supports strong relational data storage.

SQLAlchemy was used to connect Python models to database tables and keep the database work organized.

Alembic was used to manage database migrations and safely update the database structure.

Pydantic was used to validate request data and keep the API inputs clean.

JWT access tokens and refresh tokens were used to secure login sessions and protect backend routes.

Uvicorn was used to run the FastAPI development server.

Git and GitHub were used for version control, branching, collaboration, and preparing the work for group submission.

WSL Ubuntu was used to run the backend in a Linux-like development environment while working from a Windows machine.

## Running Locally

From the backend folder, create a virtual environment, install the requirements, run migrations, and start the server.

Commands:

cd backend
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
alembic upgrade head
uvicorn app.main:app --reload

The API documentation can be opened at:

http://127.0.0.1:8000/docs

## What Is Still Missing Before Final Deployment

The backend foundation is complete, but the project still needs some final production work.

Backend items still needed:

- Password reset email flow
- Email verification flow
- Full permission table wiring
- Better permission-based RBAC
- Admin user management endpoints
- Technician database-backed registry
- Department, campus, and building models
- File upload security checks
- Structured logging
- Rate limiting
- Production CORS settings
- Dockerfile
- docker-compose.yml
- Deployment documentation
- API documentation cleanup
- Automated tests

Frontend/UI items still needed:

- Login page
- Register page
- Forgot password page
- Dashboard page
- Incident creation form
- Incident list page
- Incident detail page
- Incident timeline
- Incident comments
- Status update screen
- Assignment screen
- Admin dashboard
- Role-based navigation
- Error messages
- Loading states
- Mobile responsiveness

## User Roles

Student users should be able to create incidents, view their own incidents, and comment.

Technician users should be able to view assigned incidents, update status, and comment.

Admin, Dean, and Security users should be able to view the dashboard, view all incidents, assign incidents, view history, and access analytics.

## Author

Joseph Oigara Ogendi

Backend contributor for the Incident Reporting Module.
