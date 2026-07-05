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

class AuditEventMapper:

    @staticmethod
    def to_service_call(
        audit_service,
        event,
    ):

        if isinstance(
            event,
            IncidentCreatedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "INCIDENT_CREATED",

                actor=
                    event.reporter_id,

                details=json.dumps(
                    {
                        "incident_number": event.incident_number,
                        "category": event.category,
                        "priority": event.priority,
                    }
                ),
            )

        if isinstance(
            event,
            StatusChangedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "STATUS_CHANGED",

                actor=
                    event.actor,

                details=json.dumps(
                    {
                        "field": "status",
                        "old": event.old_status,
                        "new": event.new_status,
                    }
                ),
            )

        # Incident Resolved

        if isinstance(
            event,
            IncidentResolvedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "INCIDENT_RESOLVED",

                actor=
                    event.actor,

                details=json.dumps(
                    {
                        "field": "status",
                        "new": "RESOLVED",
                    }
                ),
            )

        # Incident Closed

        if isinstance(
            event,
            IncidentClosedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "INCIDENT_CLOSED",

                actor=
                    event.actor,

                details=json.dumps(
                    {
                        "field": "status",
                        "new": "CLOSED",
                    }
                ),

            )

        # Incident Escalated

        if isinstance(
            event,
            IncidentEscalatedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "INCIDENT_ESCALATED",

                actor=
                    event.actor,

                details=json.dumps(
                    {
                        "escalation_level":
                            event.escalation_level,
                    }
                ),
            )

        # Technician Assigned

        if isinstance(
            event,
            TechnicianAssignedEvent,
        ):

            return audit_service.log(
                incident_id=event.incident_id,
                event_type="TECHNICIAN_ASSIGNED",
                actor=event.actor,
                details={
                    "old":event.previous_technician,
                    "new":event.technician_id,
                    "reason":event.assignment_reason,
                },
            )

        # Comment Added

        if isinstance(
            event,
            CommentAddedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "COMMENT_ADDED",

                actor=
                    event.author,

                details=json.dumps(
                    {
                        "comment": event.comment,
                    }
                ),
            )

        # Attachment Added

        if isinstance(
            event,
            AttachmentAddedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "ATTACHMENT_ADDED",

                actor=
                    event.actor,

                details=json.dumps(
                    {
                        "attachment": event.filename,
                    }
                ),
            )

        # Priority Changed

        if isinstance(
            event,
            PriorityChangedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "PRIORITY_CHANGED",

                actor=
                    event.actor,

                details=json.dumps(
                    {
                        "field": "priority",
                        "old": event.old_priority,
                        "new": event.new_priority,
                    }
                ),
            )

        # Risk Recalculated

        if isinstance(
            event,
            RiskRecalculatedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "RISK_RECALCULATED",

                actor=
                    "SYSTEM",

                details=json.dumps(
                    {
                        "old_score": event.previous_score,
                        "new_score": event.new_score,
                    }
                ),
            )

        # SLA Started

        if isinstance(
            event,
            SLAStartedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "SLA_STARTED",

                actor=
                    "SYSTEM",

                details=json.dumps(
                    {
                        "status": "STARTED",
                    }
                ),
            )

        # SLA Breached

        if isinstance(
            event,
            SLABreachedEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "SLA_BREACHED",

                actor=
                    "SYSTEM",

                details=json.dumps(
                    {
                        "breached_rule":
                            event.breached_rule,
                    }
                ),
            )

        # SLA Recovered

        if isinstance(
            event,
            SLARecoveredEvent,
        ):

            return audit_service.log(

                incident_id=
                    event.incident_id,

                event_type=
                    "SLA_RECOVERED",

                actor=
                    "SYSTEM",

                details=json.dumps(
                    {
                        "status": "RECOVERED",
                    }
                ),
            )

        raise ValueError(
            f"Unsupported event type: {type(event).__name__}"
        )
