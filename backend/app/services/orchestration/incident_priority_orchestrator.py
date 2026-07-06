from datetime import datetime

from app.domain.events import PriorityChangedEvent
from app.domain.exceptions import IncidentNotFound

class IncidentPriorityOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    # Change priority

    def change_priority(
        self,
        incident_id,
        new_priority,
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

        old_priority = (
            incident.priority
        )

        incident.priority = (
            new_priority
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
            PriorityChangedEvent(
                occurred_at=
                    datetime.utcnow(),

                incident_id=
                    incident.id,

                actor=
                    actor,

                old_priority=
                    old_priority,

                new_priority=
                    new_priority,
            )
        )

        return incident
