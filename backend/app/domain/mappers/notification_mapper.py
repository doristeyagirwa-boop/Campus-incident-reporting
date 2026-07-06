from app.domain.events import (
    IncidentCreatedEvent,
    StatusChangedEvent,
    IncidentResolvedEvent,
    IncidentClosedEvent,
    IncidentEscalatedEvent,
    TechnicianAssignedEvent,
    CommentAddedEvent,
    AttachmentAddedEvent,
    PriorityChangedEvent,
    RiskRecalculatedEvent,
    SLAStartedEvent,
    SLABreachedEvent,
    SLARecoveredEvent,
)


class NotificationEventMapper:

    @staticmethod
    def to_service_call(notification_service, event):

        if isinstance(event, IncidentCreatedEvent):
            return notification_service.incident_created(event)

        if isinstance(event, StatusChangedEvent):
            return notification_service.status_changed(event)

        if isinstance(event, IncidentResolvedEvent):
            return notification_service.incident_resolved(event)

        if isinstance(event, IncidentClosedEvent):
            return notification_service.incident_closed(event)

        if isinstance(event, IncidentEscalatedEvent):
            return notification_service.incident_escalated(event)

        if isinstance(event, TechnicianAssignedEvent):
            return notification_service.technician_assigned(event)

        if isinstance(event, CommentAddedEvent):
            return notification_service.comment_added(event)

        if isinstance(event, AttachmentAddedEvent):
            return notification_service.attachment_added(event)

        if isinstance(event, PriorityChangedEvent):
            return notification_service.priority_changed(event)

        if isinstance(event, RiskRecalculatedEvent):
            return notification_service.risk_recalculated(event)

        if isinstance(event, SLAStartedEvent):
            return notification_service.sla_started(event)

        if isinstance(event, SLABreachedEvent):
            return notification_service.sla_breached(event)

        if isinstance(event, SLARecoveredEvent):
            return notification_service.sla_recovered(event)

        raise ValueError(
            f"Unsupported event type: {type(event).__name__}"
        )
