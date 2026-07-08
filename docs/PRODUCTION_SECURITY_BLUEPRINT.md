# CampusGuard Production Security Blueprint

## 1. Storage and Data Protection

Current demo stack:
- PHP
- MySQL/MariaDB
- Local Ollama
- Localhost presentation mode

Production target:
- PostgreSQL
- Isolated schema/database per department
- PostgreSQL Row-Level Security
- Department-scoped service users
- No cross-department joins
- AES-256-GCM encryption at rest
- Department-specific KMS keys
- mTLS for service-to-service traffic

Department boundaries:
- Finance sees finance clearance only.
- Lecturers see academic records for their class only.
- Registrar sees academic eligibility and attendance status.
- Dean sees student risk summaries only.
- Dining sees food-service issues only.
- Facilities sees facility issues only.
- Security sees safety issues only.
- Admin sees command-level audit and routing.

## 2. Audit Trail and Workflow State

Implemented foundation:
- Append-only compliance ledger table.
- Hash-chained event records.
- Non-reversible entity identifiers using HMAC.
- High-alert token table.
- Phoenix AI and eligibility review write compliance events.

Production target:
- Database write restrictions preventing UPDATE/DELETE on ledger.
- External WORM archive.
- Event bus for every read/write.
- Strict state machine for high-alert tokens.
- RBAC-mapped JWT claims for workflow routing.

## 3. Inter-Agency Integration

Current demo:
- Localhost-only.
- No external SMS/email dispatch.
- Message queue stored locally.

Production target:
- REST and gRPC endpoints behind API gateway.
- OAuth2 and mTLS.
- Webhook signing.
- Rate limiting for AI and incident endpoints.
- Tokenized payloads only.
- Campus Police integration uses non-reversible identifiers unless lawful disclosure is authorized.

## 4. AI Inference Security

Current demo:
- Ollama runs locally.
- Phoenix AI runs in PHP backend.
- Normal users do not see AI internals.

Production target:
- AI inference service isolated in private subnet.
- No direct browser access to AI service.
- Background queue workers.
- Rate-limited model calls.
- Prompt and response audit hashing.
- No sensitive finance or medical data sent to a model unless explicitly authorized.

## 5. Kenya Data Protection and FERPA Alignment

Design principles:
- Data minimization.
- Purpose limitation.
- Role-based access.
- Auditability.
- Confidentiality.
- No unnecessary cross-office exposure.
- Admin access is logged.
- Staff dashboards show only what each office needs to do its job.
