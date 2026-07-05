from datetime import datetime

from app.domain.events import IncidentEscalatedEvent
from app.domain.exceptions import IncidentNotFound

class IncidentEscalationOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    # Escalate incident

    def escalate(
        self,
        incident_id,
        escalation_level,
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

        incident.escalation_level = (
            escalation_level
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
            IncidentEscalatedEvent(

                occurred_at=
                    datetime.utcnow(),

                incident_id=
                    incident.id,

                actor=
                    actor,

                escalation_level=
                    escalation_level,

            )
        )

        return incident
