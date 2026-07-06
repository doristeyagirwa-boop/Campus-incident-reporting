from uuid import UUID

from sqlalchemy import func
from sqlalchemy.orm import Session
from sqlalchemy.orm import Session

from app.models.incident_history import IncidentHistory

class HistoryRepository:

    def __init__(
        self,
        db: Session,
    ):
        self.db = db

    def create(
        self,
        history: IncidentHistory,
    ):

        self.db.add(
            history
        )

        self.db.flush()

        self.db.refresh(
            history
        )

        return history

    def create_event(
        self,
        incident_id: UUID,
        event_type: str,
        performed_by: str,
        old_value: str = None,
        new_value: str = None,
        field_name: str = None,
        reason: str = None,
    ):

        history = IncidentHistory(
            incident_id=incident_id,
            event_type=event_type,
            field_name=field_name,
            old_value=old_value,
            new_value=new_value,
            change_reason=reason,
            performed_by=performed_by,
        )

        self.db.add(history)
        self.db.flush()
        self.db.refresh(history)

        return history

    def get_incident_timeline(
        self,
        incident_id: UUID,
    ):
        return (
            self.db.query(
                IncidentHistory
            )
            .filter(
                IncidentHistory.incident_id
                == incident_id
            )
            .order_by(
                IncidentHistory.created_at.asc()
            )
            .all()
        )

    def get_incident_history(
        self,
        incident_id,
    ):

        return (
            self.db.query(
                IncidentHistory
            )
            .filter(
                IncidentHistory.incident_id
                ==
                incident_id
            )
            .order_by(
                IncidentHistory.created_at.asc()
            )
            .all()
        )

    def count_events(
        self,
        incident_id,
    ):

        return (
            self.db.query(
                func.count(
                    IncidentHistory.id
                )
            )
            .filter(
                IncidentHistory.incident_id
                == incident_id
            )
            .scalar()
        )

    def latest_event(
        self,
        incident_id,
    ):

        return (
            self.db.query(
                IncidentHistory
            )
            .filter(
                IncidentHistory.incident_id
                == incident_id
            )
            .order_by(
                IncidentHistory.created_at.desc()
            )
            .first()
        )

    def get_events_by_type(
        self,
        incident_id,
        event_type,
    ):

        return (
            self.db.query(
                IncidentHistory
            )
            .filter(
                IncidentHistory.incident_id
                == incident_id,
                IncidentHistory.event_type
                == event_type,
            )
            .order_by(
                IncidentHistory.created_at.asc()
            )
            .all()
        )

    def get_events_by_actor(
        self,
        actor,
    ):

        return (
            self.db.query(
                IncidentHistory
            )
            .filter(
            IncidentHistory.performed_by
            == actor
        )
        .order_by(
            IncidentHistory.created_at.desc()
        )
        .all()
    )

        def latest_status_change(
            self,
            incident_id,
        ):

            return (
                self.db.query(
                    IncidentHistory
                )
                .filter(
                    IncidentHistory.incident_id
                    == incident_id,
                    IncidentHistory.event_type
                    == "STATUS_CHANGE",
                )
                .order_by(
                    IncidentHistory.created_at.desc()
                )
                .first()
            )
