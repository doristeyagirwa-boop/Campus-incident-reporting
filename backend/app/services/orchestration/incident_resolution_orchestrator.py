from datetime import datetime

from app.domain.events import IncidentResolvedEvent
from app.domain.exceptions import IncidentNotFound

class IncidentResolutionOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    # Resolve incident

    def resolve(
        self,
        incident_id,
        actor,
    ):

        incident = (
            self.context
            .incident_repository
            .get_by_id(
                incident_id
            )
        )

        if incident is None:
            raise IncidentNotFound(
                "Incident not found."
            )

        incident.status = (
            "RESOLVED"
        )

        incident.resolved_at = (
            datetime.utcnow()
        )

        incident.updated_at = (
            datetime.utcnow()
        )

        incident = (
            self.context
            .incident_repository
            .update(
                incident
            )
        )

        self.context.event_publisher.publish(
            IncidentResolvedEvent(

                occurred_at=
                    datetime.utcnow(),

                incident_id=
                    incident.id,

                actor=
                    actor,
            )
        )

        return incident
