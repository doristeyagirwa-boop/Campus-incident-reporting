from app.services.orchestration.incident_creation_orchestrator import IncidentCreationOrchestrator
from app.services.orchestration.incident_retrieval_orchestrator import IncidentRetrievalOrchestrator
from app.services.orchestration.incident_status_orchestrator import IncidentStatusOrchestrator
from app.services.orchestration.incident_timeline_orchestrator import IncidentTimelineOrchestrator
from app.services.orchestration.incident_sla_orchestrator import IncidentSLAOrchestrator
from app.services.orchestration.incident_history_orchestrator import IncidentHistoryOrchestrator
from app.services.orchestration.incident_analytics_orchestrator import IncidentAnalyticsOrchestrator
from app.services.orchestration.incident_comment_orchestrator import IncidentCommentOrchestrator
from app.services.orchestration.incident_assignment_orchestrator import IncidentAssignmentOrchestrator
from app.services.orchestration.incident_attachment_orchestrator import IncidentAttachmentOrchestrator
from app.services.orchestration.incident_priority_orchestrator import IncidentPriorityOrchestrator
from app.services.orchestration.incident_escalation_orchestrator import IncidentEscalationOrchestrator
from app.services.orchestration.incident_resolution_orchestrator import IncidentResolutionOrchestrator
from app.services.orchestration.incident_closure_orchestrator import IncidentClosureOrchestrator

class IncidentApplicationService:

    def __init__(
        self,
        context,
        container,
    ):
        self.context = context
        self.container = container

        self.creation_orchestrator = (
            IncidentCreationOrchestrator(
                context,
                container,
            )
        )

        self.retrieval_orchestrator = (
            IncidentRetrievalOrchestrator(
                context
            )
        )

        self.status_orchestrator = (
            IncidentStatusOrchestrator(
                context
            )
        )

        self.timeline_orchestrator = (
            IncidentTimelineOrchestrator(
                context
            )
        )

        self.sla_orchestrator = (
            IncidentSLAOrchestrator(
                context
            )
        )

        self.history_orchestrator = (
            IncidentHistoryOrchestrator(
                context
            )
        )

        self.analytics_orchestrator = (
            IncidentAnalyticsOrchestrator(
                context
            )
        )

        self.comment_orchestrator = (
            IncidentCommentOrchestrator(
                context
            )
        )

        self.assignment_orchestrator = (
            IncidentAssignmentOrchestrator(
                context,
                container.assignment_policy,
            )
        )

        self.attachments_orchestrator = (
            IncidentAttachmentOrchestrator(
                context,
            )
        )

        self.priority_orchestrator = (
            IncidentPriorityOrchestrator(
                context,
            )
        )

        self.escalation_orchestrator = (
            IncidentEscalationOrchestrator(
                context,
            )
        )

        self.resolution_orchestrator = (
            IncidentResolutionOrchestrator(
                context,
            )
        )

        self.closure_orchestrator = (
            IncidentClosureOrchestrator(
                context,
            )
        )

    def create_incident(
        self,
        payload,
    ):
        with self.context.unit_of_work:
            incident = (
                self.creation_orchestrator
                .create_incident(

                    title=payload.title,
                    description=
                        payload.description,

                    category=
                        payload.category,

                    location=
                        payload.location,

                    reporter_id=
                        payload.reporter_id,

                    affected_users=
                        payload.affected_users,
                )
            )
        return incident

    def list_incidents(
        self,
        filters,
    ):
        return (
            self.analytics_orchestrator
            .list_incidents(
                filters
            )
        )

    def get_incident(
        self,
        incident_id,
    ):

        return (
            self.retrieval_orchestrator
            .get_incident(
                incident_id
            )
        )

    def update_status(
        self,
        incident_id,
        target_status,
        actor,
    ):

        with self.context.unit_of_work:
            incident = (
                self.status_orchestrator
                .transition(
                    incident_id,
                    target_status,
                    actor,
                )
            )

        return incident

    def get_history(
        self,
        incident_id,
    ):
        return (
            self.history_orchestrator
            .get_history(
                incident_id
            )
        )

    def get_metrics(
        self,
    ):

        return (
            self.analytics_orchestrator
            .get_metrics()
        )

    def get_timeline(
        self,
        incident_id,
    ):

        return (
            self.timeline_orchestrator
            .get_timeline(
                incident_id
            )
        )

    def add_comment(
        self,
        incident_id,
    	payload,
    ):

        with self.context.unit_of_work:
            comment = (
                self.comment_orchestrator
                .add_comment(
                    incident_id,
                    payload.author,
                    payload.comment,
                )

            )

        return comment

    def get_comments(
        self,
        incident_id,
    ):

        return (
            self.comment_orchestrator
            .get_comments(
                incident_id
            )
        )

    def assign_technician(
        self,
        incident_id,
        technician_id,
        actor,
        reason,
    ):

        with self.context.unit_of_work:
            incident = (
                self.assignment_orchestrator
                .assign(
                    incident_id,
                    technician_id,
                    actor,
                    reason,
                )
            )

        return incident

    def add_attachment(
        self,
        incident_id,
        filename,
        storage_path,
        uploaded_by,
    ):

        with self.context.unit_of_work:
            incident = (
                self.attachments_orchestrator
                .add_attachment(
                    incident_id,
                    filename,
                    storage_path,
                    uploaded_by,
                )
            )

        return incident

    def change_priority(
        self,
        incident_id,
        priority,
        actor,
    ):

        with self.context.unit_of_work:
            incident = (
                self.priority_orchestrator
                .change_priority(
                    incident_id,
                    priority,
                    actor,
                )
            )

        return incident

    def escalate_incident(
        self,
        incident_id,
        escalation_level,
        actor,
    ):

        with self.context.unit_of_work:
            incident = (
                self.escalation_orchestrator
                .escalate(
                    incident_id,
                    escalation_level,
                    actor,
                )
            )

        return incident

    def resolve_incident(
        self,
        incident_id,
        actor,
    ):

        with self.context.unit_of_work:
            incident = (
                self.resolution_orchestrator
                .resolve(
                    incident_id,
                    actor,
                )
            )

        return incident

    def close_incident(
        self,
        incident_id,
        actor,
    ):

        with self.context.unit_of_work:
            incident = (
                self.closure_orchestrator
                .close(
                    incident_id,
                    actor,
                )
            )

        return incident
