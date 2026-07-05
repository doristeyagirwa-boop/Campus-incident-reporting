from datetime import datetime

from sqlalchemy import and_
from sqlalchemy import func
from sqlalchemy import or_

from uuid import UUID

from sqlalchemy.orm import Session

from app.models.incident import Incident


class IncidentRepository:

    def __init__(
        self,
        db: Session,
    ):
        self.db = db

    def create(
        self,
        incident: Incident,
    ):
        self.db.add(incident)
        self.db.commit()
        self.db.refresh(incident)

        return incident

    def update(
        self,
        incident: Incident,
    ):
        self.db.commit()

        self.db.refresh(
            incident
        )

        return incident

    def get_by_id(
        self,
        incident_id: UUID,
    ):
        return (
            self.db.query(Incident)
            .filter(
                Incident.id == incident_id
            )
            .first()
        )

    def get_by_incident_number(
        self,
        incident_number: str,
    ):
        return (
            self.db.query(Incident)
            .filter(
                Incident.incident_number
                == incident_number
            )
            .first()
        )

    def list_all(
        self,
        limit: int = 100,
        offset=0,
    ):
        return (
            self.db.query(Incident)
            .limit(limit)
            .all()
        )

    def get_all(
        self,
    ):

        return (
            self.db.query(
                Incident
            )
            .all()
        )

    def get_by_status(
        self,
        status,
    ):
        return (
            self.db.query(
                Incident
            )
            .filter(
                Incident.status
                == status
            )
            .all()
        )

    def get_by_priority(
        self,
        priority,
    ):
        return (
            self.db.query(
                Incident
            )
            .filter(
                Incident.priority
                == priority
            )
            .all()
        )

    def get_by_reporter(
        self,
        reporter_id,
    ):
        return (
            self.db.query(
                Incident
            )
            .filter(
                Incident.reporter_id
                == reporter_id
            )
            .all()
        )

    def search(
        self,
        filters,
    ):

        query = self.db.query(
            Incident
        )

        if filters.status:
            query = query.filter(
                Incident.status ==
                filters.status
            )

        if filters.priority:
            query = query.filter(
                Incident.priority ==
                filters.priority
            )

        if filters.category:
            query = query.filter(
                Incident.category ==
                filters.category
            )

        if filters.location:
            query = query.filter(
                Incident.location.ilike(
                    f"%{filters.location}%"
                )
            )

        if filters.reporter_id:
            query = query.filter(
                Incident.reporter_id ==
                filters.reporter_id
            )

        if (
            filters.assigned_technician_id
        ):
            query = query.filter(
                Incident.assigned_technician_id
                ==
                filters.assigned_technician_id
            )

        total = query.count()

        sort_column = getattr(
            Incident,
            filters.sort_by,
            Incident.created_at,
        )

        if (
            filters.sort_direction
            ==
            "asc"
        ):
            query = query.order_by(
                sort_column.asc()
            )
        else:
            query = query.order_by(
                sort_column.desc()
            )

        incidents = (
            query
            .offset(
                (
                    filters.page - 1
                )
                *
                filters.page_size
            )
            .limit(
                filters.page_size
            )
            .all()
        )

        return incidents, total

    def count_all(self):
        return (
            self.db.query(
                func.count(
                    Incident.id
                )
            )
            .scalar()
        )

    def get_open_incidents(
        self,
    ):

        return (
            self.db.query(
                Incident
            )
            .filter(
                Incident.status.in_(
                    [
                        "SUBMITTED",
                        "ASSIGNED",
                        "INVESTIGATING",
                    ]
                )
            )
            .all()
        )

    def next_sequence(self):
        count = (
            self.db.query(
                func.count(
                    Incident.id
                )
            )
            .scalar()
        )

        return count + 1

    def find_possible_duplicate(
        self,
        title,
        description,
    ):

        return None

    def assign_technician(
        self,
        incident,
        technician_id,
    ):

        incident.assigned_technician=technician_id
        incident.updated_at = datetime.utcnow()

        self.db.flush()
        self.db.refresh(incident)

        return incident
