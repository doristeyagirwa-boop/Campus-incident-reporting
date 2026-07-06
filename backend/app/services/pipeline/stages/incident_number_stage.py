from app.algorithms.incident_number_generator import IncidentNumberGenerator

from app.domain.stage_result import StageResult

from app.services.pipeline.pipeline_stage import PipelineStage

class IncidentNumberStage(
    PipelineStage,
):

    # Generate incident identifier.

    def __init__(
        self,
        repository,
    ):

        self.repository = repository

    def execute(
        self,
        context,
    ):

        context.incident_number = (
            IncidentNumberGenerator.generate(
                category=
                    context.category,
                sequence=
                    self.repository
                    .next_sequence(),
            )
        )

        return StageResult(
            stage_name=
                "Incident Number",
            success=
                True,
            message=
                "Incident number generated.",
        )
