from sqlalchemy.orm import Session

from app.models.audit_log import AuditLog


class AuditRepository:

    def __init__(
        self,
        db: Session,
    ):
        self.db = db

    # Persist a new audit record.
    def create(
        self,
        audit: AuditLog,
    ):

        self.db.add(
            audit
        )

        self.db.commit()

        self.db.refresh(
            audit
        )

        return audit

    # Retrieve all audit records.
    def list_all(
        self,
    ):

        return (

            self.db.query(
                AuditLog
            )

            .order_by(
                AuditLog.created_at.desc()
            )

            .all()
        )

    # Retrieve audit records for an incident.
    def get_by_incident(
        self,
        incident_id: str,
    ):

        return (

            self.db.query(
                AuditLog
            )

            .filter(
                AuditLog.incident_id
                ==
                incident_id
            )

            .order_by(
                AuditLog.created_at.asc()
            )

            .all()
        )

    # Retrieve audit records by actor.
    def get_by_actor(
        self,
        actor: str,
    ):

        return (

            self.db.query(
                AuditLog
            )

            .filter(
                AuditLog.actor
                ==
                actor
            )

            .order_by(
                AuditLog.created_at.desc()
            )

            .all()
        )
