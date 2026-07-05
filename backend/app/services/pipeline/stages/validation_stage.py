import time

from app.services.pipeline.pipeline_stage import PipelineStage

from app.domain.pipeline_context import PipelineContext
from app.domain.stage_result import StageResult
from app.domain.validation import IncidentValidator

class ValidationStage(
    PipelineStage,
):

    # Validate incident request

    def execute(
        self,
        context: PipelineContext,
    ) -> StageResult:

        started = (
            time.perf_counter()
        )

        IncidentValidator.validate_title(
            context.title
        )

        IncidentValidator.validate_description(
            context.description
        )

        IncidentValidator.validate_location(
            context.location
        )

        IncidentValidator.validate_reporter(
            context.reporter_id
        )

        return StageResult(

            stage_name=
                "Validation",

            success=True,

            message=
                "Validation completed.",

            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                *
                1000,
        )
