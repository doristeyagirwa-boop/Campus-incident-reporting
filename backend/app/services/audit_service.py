from app.models.audit_log import AuditLog

from app.repositories.audit_repository import (
    AuditRepository,
)

class AuditService:

    def __init__(
        self,
        repository: AuditRepository,
    ):

        self.repository = repository

    # Persist an audit record.
    def log(
        self,
        incident_id,
        event_type,
        actor,
        details=None,
        source_system="WEB",
        request_id=None,
        correlation_id=None,
        session_id=None,
        ip_address=None,
        service_name="IncidentReportModule",
        is_success=True,
    ):

        audit = AuditLog(
            incident_id=incident_id,
            event_type=event_type,
            actor=actor,
            details=details,
            source_system=source_system,
            request_id=request_id,
            correlation_id=correlation_id,
            session_id=session_id,
            ip_address=ip_address,
            service_name=service_name,
            is_success=is_success,
        )

        return (

            self.repository
            .create(
                audit
            )
        )
