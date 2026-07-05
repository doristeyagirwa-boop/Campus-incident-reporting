import time

from app.services.pipeline.pipeline_stage import PipelineStage

from app.domain.pipeline_context import PipelineContext
from app.domain.stage_result import StageResult


class PriorityStage(PipelineStage):

    def __init__(
        self,
        priority_engine,
    ):
        self.priority_engine = (
            priority_engine
        )

    # Calculate incident priority

    def execute(
        self,
        context: PipelineContext,
    ) -> StageResult:

        started = (
            time.perf_counter()
        )

        context.priority = (
            self.priority_engine.calculate_priority(

                severity_score=
                    context.severity_score,

                affected_users=
                    context.affected_users,

                risk_score=
                    context.risk_score,

                historical_frequency=
                    context.historical_frequency,
            )
        )

        return StageResult(

            stage_name=
                "Priority",

            success=True,

            message=
                "Priority calculated.",

            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                * 1000,
        )
