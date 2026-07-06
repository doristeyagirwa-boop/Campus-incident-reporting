from app.domain.events import TechnicianAssignedEvent

from datetime import datetime

from app.domain.exceptions import IncidentNotFound

class IncidentAssignmentOrchestrator:

    def __init__(
        self,
        context,
        assignment_policy,
    ):
        self.context=context
        self.assignment_policy=assignment_policy

    # Assign technician

    def assign(
        self,
        incident_id,
        technician_id,
        actor,
        reason,
    ):

        incident = (
            self.context
            .incident_repository
            .get_by_id(
                incident_id
            )
        )

        technician=(
            self.context
            .technician_registry
            .get(
                technician_id
            )
        )

        self.assignment_policy.validate(
            incident,
            technician,
        )

        if incident is None:
            raise IncidentNotFound(
                "Incident not found."
            )

        previous = incident.assigned_technician

        incident.assigned_technician = (
            technician_id
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
            TechnicianAssignedEvent(
                occurred_at=datetime.utcnow(),
                incident_id=incident.id,
                actor=actor,
                technician_id=technician_id,
                previous_technician=previous,

            )
        )

        return incident
