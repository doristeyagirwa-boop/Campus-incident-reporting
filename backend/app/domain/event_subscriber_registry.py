from app.domain.events import (
    IncidentCreatedEvent,
    StatusChangedEvent,
    CommentAddedEvent,
    TechnicianAssignedEvent,
    AttachmentAddedEvent,
    PriorityChangedEvent,
    RiskRecalculatedEvent,
    SLAStartedEvent,
    SLABreachedEvent,
    SLARecoveredEvent,
    IncidentEscalatedEvent,
    IncidentResolvedEvent,
    IncidentClosedEvent,
)


class EventSubscriberRegistry:

    @staticmethod
    def register(
        publisher,
        *listeners,
    ):

        # Incident lifecycle

        lifecycle_events = (
            IncidentCreatedEvent,
            StatusChangedEvent,
            IncidentResolvedEvent,
            IncidentClosedEvent,
            IncidentEscalatedEvent,
        )

        # Operational events

        operational_events = (
            TechnicianAssignedEvent,
            AttachmentAddedEvent,
            PriorityChangedEvent,
            RiskRecalculatedEvent,
            SLAStartedEvent,
            SLABreachedEvent,
            SLARecoveredEvent,
            CommentAddedEvent,
        )

        for event in (
            lifecycle_events
            +
            operational_events
        ):

            for listener in listeners:
                publisher.subscribe(
                    event,
                    listener,
                )
