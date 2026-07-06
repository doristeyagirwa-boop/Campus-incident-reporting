from app.domain.state_machine import IncidentStateMachine
from app.domain.events import StatusChangedEvent

from datetime import datetime

class IncidentStatusOrchestrator:

    def __init__(self,context):
        self.context=context

    def transition(
        self,
        incident_id,
        target_status,
        actor,
    ):

        incident=(
            self.context
            .incident_repository
            .get_by_id(
                incident_id
            )
        )

        if incident is None:
            raise ValueError(
                "Incident not found."
            )

        allowed=(
            IncidentStateMachine
            .validate_transition(
                incident.status,
                target_status,
            )
        )

        if not allowed:
            raise ValueError(
                f"Illegal transition "
                f"{incident.status}"
                f" -> "
                f"{target_status}"
            )

        previous_status=incident.status

        incident.status=target_status

        incident.updated_at=datetime.utcnow()

        incident=(
            self.context
            .incident_repository
            .update(
                incident
            )
        )

        self.context.event_publisher.publish(

            StatusChangedEvent(

                occurred_at=datetime.utcnow(),

                incident_id=incident.id,

                actor=actor,

                old_status=previous_status,

                new_status=target_status,
            )
        )

        return incident
