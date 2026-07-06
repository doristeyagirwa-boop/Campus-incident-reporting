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
import json

class HistoryEventMapper:

    @staticmethod
    def to_repository_call(
        repository,
        event,
    ):

        # Incident Created

        if isinstance(
            event,
            IncidentCreatedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "INCIDENT_CREATED",

                performed_by=
                    event.reporter_id,

                field_name=
                    "incident",

                old_value=
                    None,

                new_value=json.dumps(
                    {
                    "incident_number": event.incident_number,
                    "category": event.category,
                    "priority": event.priority,
                    }
                )
            )

        # Status Changed

        if isinstance(
            event,
            StatusChangedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "STATUS_CHANGE",

                performed_by=
                    event.actor,

                field_name=
                    "status",

                old_value=
                    event.old_status,

                new_value=
                    event.new_status,
            )

        # Comment Added

        if isinstance(
            event,
            CommentAddedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "COMMENT_ADDED",

                performed_by=
                    event.author,

                field_name=
                    "comment",

                new_value=
                    event.comment,
            )

        # Incident Resolved

        if isinstance(
            event,
            IncidentResolvedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "INCIDENT_RESOLVED",

                performed_by=
                    event.actor,

                field_name=
                    "status",

                new_value=
                    "RESOLVED",
            )

        # Incident Closed

        if isinstance(
            event,
            IncidentClosedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "INCIDENT_CLOSED",

                performed_by=
                    event.actor,

                field_name=
                    "status",

                new_value=
                    "CLOSED",
            )

        # Incident Escalated

        if isinstance(
            event,
            IncidentEscalatedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "INCIDENT_ESCALATED",

                performed_by=
                    event.actor,

                field_name=
                    "escalation_level",

                new_value=
                    event.escalation_level,
            )

        # Technician Assigned

        if isinstance(
            event,
            TechnicianAssignedEvent,
        ):

            return repository.create_event(
                incident_id=event.incident_id,
                event_type="TECHNICIAN_ASSIGNED",
                performed_by=event.actor,
                field_name="assigned_technician",
                old_value=event.previous_technician,
                new_value=event.technician_id,
                reason=event.assignment_reason,
            )

        # Attachment Added

        if isinstance(
            event,
            AttachmentAddedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "ATTACHMENT_ADDED",

                performed_by=
                    event.actor,

                field_name=
                    "attachment",

                new_value=
                    event.filename,
            )

        # Priority Changed

        if isinstance(
            event,
            PriorityChangedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "PRIORITY_CHANGED",

                performed_by=
                    event.actor,

                field_name=
                    "priority",

                old_value=
                    event.old_priority,

                new_value=
                    event.new_priority,
            )

        # Risk Recalculated

        if isinstance(
            event,
            RiskRecalculatedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "RISK_RECALCULATED",

                performed_by=
                    "SYSTEM",

                field_name=
                    "risk_score",

                old_value=
                    str(event.previous_score),

                new_value=
                    str(event.new_score),
            )

        # SLA Started

        if isinstance(
            event,
            SLAStartedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "SLA_STARTED",

                performed_by=
                    "SYSTEM",

                field_name=
                    "sla",

                new_value=
                    "STARTED",
            )

        # SLA Breached

        if isinstance(
            event,
            SLABreachedEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "SLA_BREACHED",

                performed_by=
                    "SYSTEM",

                field_name=
                    "sla",

                new_value=
                    event.breached_rule,
            )

        # SLA Recovered

        if isinstance(
            event,
            SLARecoveredEvent,
        ):

            return repository.create_event(

                incident_id=
                    event.incident_id,

                event_type=
                    "SLA_RECOVERED",

                performed_by=
                    "SYSTEM",

                field_name=
                    "sla",

                new_value=
                    "RECOVERED",
            )

        raise ValueError(
            f"Unsupported event type: {type(event).__name__}"
        )
