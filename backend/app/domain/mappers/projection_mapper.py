from app.domain.events import(
    IncidentCreatedEvent,
    StatusChangedEvent,
    TechnicianAssignedEvent,
    PriorityChangedEvent,
    IncidentEscalatedEvent,
    IncidentResolvedEvent,
    IncidentClosedEvent,
)

class ProjectionMapper:

    @staticmethod
    def update(
        projection,
        event,
    ):

        if isinstance(
            event,
            (
                IncidentCreatedEvent,
                StatusChangedEvent,
                PriorityChangedEvent,
                IncidentEscalatedEvent,
                IncidentResolvedEvent,
                IncidentClosedEvent,
            ),
        ):

            return projection.update(
                event
            )

        if isinstance(
            event,
            TechnicianAssignedEvent,
        ):

            return projection.update(
                event
            )

        return None
