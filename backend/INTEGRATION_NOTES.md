# Joseph Incident Backend Integration Notes

## Module

Incident Reporting Backend Module

## Contributor

Joseph Oigara Ogendi

## Technology

- Python
- FastAPI
- PostgreSQL
- SQLAlchemy
- Alembic
- JWT authentication
- Refresh token rotation
- Role-based endpoint protection

## Completed Backend Features

- Incident creation
- Incident listing
- Incident detail retrieval
- Incident status updates
- Incident assignment
- Incident history
- Incident timeline
- Incident comments
- Dashboard metrics
- User registration
- Login using institution ID + email + password
- JWT access token generation
- Refresh token persistence and rotation
- Logout
- Current user endpoint
- Login sessions
- Login attempt logging
- Basic role-based access protection

## Important API Endpoints

### System

- `GET /system/health`

### Authentication

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/refresh`
- `POST /auth/logout`
- `GET /auth/me`

### Incidents

- `POST /incidents/`
- `GET /incidents/`
- `GET /incidents/{incident_id}`
- `PATCH /incidents/{incident_id}/status`
- `PATCH /incidents/{incident_id}/assign`
- `GET /incidents/{incident_id}/history`
- `GET /incidents/{incident_id}/timeline`
- `POST /incidents/{incident_id}/comments`
- `GET /incidents/{incident_id}/comments`
- `GET /incidents/metrics/dashboard`

## Frontend Integration Notes

The existing PHP frontend can connect to the FastAPI backend using HTTP requests.

Recommended flow:

1. User logs in through frontend.
2. Frontend sends institution ID, email, and password to `/auth/login`.
3. Backend returns access token and refresh token.
4. Frontend sends access token as:

```http
Authorization: Bearer ACCESS_TOKEN
