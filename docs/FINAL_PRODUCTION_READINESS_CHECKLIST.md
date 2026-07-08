# CampusGuard Final Production Readiness Checklist

## Completed in Localhost Demo

### Role Privacy
- Lecturer sees attendance, grades, and eligibility state only.
- Lecturer does not see finance balances.
- Registrar sees academic eligibility and attendance status.
- Registrar does not see fee amount.
- Dean sees risk summary only.
- Finance sees fee clearance only.
- Finance does not see grades.
- Normal users do not see Phoenix AI, Ollama, LangChain, prompts, or neural internals.
- Admin sees command-level tools.

### Cognitive Incident Intelligence
- Phoenix AI domain classification implemented.
- Dining, academic, finance, facility, IT/security routing implemented.
- Local Ollama reasoning bridge implemented.
- Ollama warmup implemented.
- Exam eligibility engine implemented.
- Attendance threshold set at 70%.
- Finance clearance checked separately.
- Internal/email-style message queue implemented.

### Audit and Compliance
- Append-only compliance ledger foundation implemented.
- Hash-chained event records implemented.
- Non-reversible entity identifiers implemented through HMAC.
- High-alert compliance tokens implemented.
- Integration event queue implemented.
- Admin compliance command page implemented.

### Integration Foundation
- SIS endpoint placeholder registered.
- LMS endpoint placeholder registered.
- Campus Police token webhook placeholder registered.
- Local Ollama inference endpoint registered.
- AI inference rate-limit table implemented.
- Integration events are tokenized.
- Production external endpoints are disabled in localhost demo.

## Production Deployment Requirements

### Storage and FERPA / Kenya Data Protection Act
- Move from MySQL/MariaDB demo storage to PostgreSQL.
- Create isolated PostgreSQL schemas or databases per department.
- Enable PostgreSQL Row-Level Security.
- Use separate DB service accounts per department.
- Forbid cross-department joins.
- Use AES-256-GCM encryption at rest.
- Use unique KMS key per department.
- Store KMS key material outside the application server.
- Use mTLS for service-to-service traffic.

### Audit Trail
- Lock compliance ledger against UPDATE and DELETE.
- Mirror ledger to WORM storage.
- Add database-level triggers for all sensitive reads and writes.
- Add JWT/RBAC state machine for token evaluation.
- Require admin actions to be logged with reason codes.

### Inter-Agency and API Security
- Put REST/gRPC APIs behind an API gateway.
- Enforce OAuth2 with mTLS.
- Sign all webhooks.
- Rate-limit all external and AI inference endpoints.
- Send only tokenized payloads to external agencies.
- Keep AI inference in private subnet.
- Do not expose Ollama directly to browsers or public networks.

## Presentation Statement

CampusGuard is a localhost demonstration of a cognitive campus incident reporting module. The staff interface is intentionally simple, while the backend performs silent classification, routing, eligibility checks, audit logging, tokenization, and local AI reasoning. Production deployment requires PostgreSQL RLS, departmental isolation, KMS encryption, mTLS, API gateway controls, and an isolated AI inference subnet.
