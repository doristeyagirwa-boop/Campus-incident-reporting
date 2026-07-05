import time
from datetime import datetime

from app.services.pipeline.pipeline_stage import PipelineStage

from app.domain.pipeline_context import PipelineContext
from app.domain.stage_result import StageResult
from app.domain.events import IncidentCreatedEvent

from app.models.incident import Incident

class PersistenceStage(PipelineStage):

    def __init__(
        self,
        repository,
    ):

        self.repository = (
            repository
        )

    # Persist incident

    def execute(
        self,
        context: PipelineContext,
    ) -> StageResult:

        started = (
            time.perf_counter()
        )

        incident = Incident(

            incident_number=
                context.incident_number,

            title=
                context.title,

            description=
                context.description,

            category=
                context.category,

            location=
                context.location,

            reporter_id=
                context.reporter_id,

            priority=
                context.priority,

            severity_score=
                context.severity_score,

            risk_score=
                context.risk_score,

            similarity_score=
                context.similarity_score,

            affected_users=
                context.affected_users,

            response_due_at=
                context.response_due_at,

            resolution_due_at=
                context.resolution_due_at,

            assigned_technician_id=
                getattr(
                    context.assigned_technician,
                    "id",
                    None,
                ),
        )

        incident = self.repository.create(
            incident
        )

        context.incident = incident

        context.created_incident = incident

        context.events.append(
            IncidentCreatedEvent(
                occurred_at=datetime.utcnow(),
                incident_id=incident.id,
                incident_number=incident.incident_number,
                category=incident.category,
                reporter_id=incident.reporter_id,
                priority=incident.priority,
            )
        )

        return StageResult(

            stage_name=
                "Persistence",

            success=True,

            message=
                "Incident persisted.",

            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                *
                1000,
        )
