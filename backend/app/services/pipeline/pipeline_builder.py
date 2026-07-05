from app.services.pipeline.incident_pipeline import IncidentPipeline
from app.services.pipeline.stages.validation_stage import ValidationStage
from app.services.pipeline.stages.classification_stage import ClassificationStage
from app.services.pipeline.stages.duplicate_detection_stage import DuplicateDetectionStage
from app.services.pipeline.stages.priority_stage import PriorityStage
from app.services.pipeline.stages.risk_stage import RiskStage
from app.services.pipeline.stages.assignment_stage import AssignmentStage
from app.services.pipeline.stages.policy_stage import PolicyStage
from app.services.pipeline.stages.sla_stage import SLAStage
from app.services.pipeline.stages.persistence_stage import PersistenceStage
from app.services.pipeline.stages.incident_number_stage import IncidentNumberStage
from app.services.pipeline.stages.event_stage import EventStage

class PipelineBuilder:

    @staticmethod
    def build(
        context,
        container,

    ):

        pipeline = (
            IncidentPipeline()
        )

        # Validation

        pipeline.add_stage(
            ValidationStage()
        )

        # Classification

        pipeline.add_stage(
            ClassificationStage(
                classification_engine=
                    container.classification_engine,
            )
        )

        # Duplicate Detection

        pipeline.add_stage(
            DuplicateDetectionStage(
                repository=
                    context.incident_repository,
                similarity_engine=
                    container.similarity_engine,
            )
        )

        # Risk

        pipeline.add_stage(
            RiskStage(
                risk_engine=
                    container.risk_engine,
            )
        )

        # Priority

        pipeline.add_stage(
            PriorityStage(
                priority_engine=
                    container.priority_engine,
            )
        )

        # Assignment

        pipeline.add_stage(
            AssignmentStage(
                assignment_engine=
                    container.assignment_engine,

                technician_registry=
                    container.technician_registry,
            )
        )

        # Policy

        pipeline.add_stage(
            PolicyStage(
                policy_registry=
                    context.policy_registry,
            )
        )

        # SLA

        pipeline.add_stage(
            SLAStage(
                sla_engine=
                    container.sla_engine,
            )
        )

        # Incident Number

        pipeline.add_stage(
            IncidentNumberStage(
                repository=
                    context.incident_repository,
            )
        )

        # Persistence

        pipeline.add_stage(
            PersistenceStage(
                repository=
                    context.incident_repository,
            )
        )

        # Events

        pipeline.add_stage(
            EventStage(
                publisher=
                    context.event_publisher,
            )
        )

        return pipeline
