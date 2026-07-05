import time

from app.services.pipeline.pipeline_stage import PipelineStage

from app.domain.pipeline_context import PipelineContext
from app.domain.stage_result import StageResult


class PolicyStage(PipelineStage):

    def __init__(
        self,
        policy_registry,
    ):

        self.policy_registry = (
            policy_registry
        )

    # Evaluate governance policies

    def execute(
        self,
        context: PipelineContext,
    ) -> StageResult:

        started = (
            time.perf_counter()
        )

        result = (

            self.policy_registry
            .evaluate(

                category=context.category,
                reporter_id=context.reporter_id,
                priority=context.priority,
                risk_score=context.risk_score,
            )
        )

        context.compliance_result = (
            result
        )

        context.approval_required = (
            result.requires_approval
        )

        return StageResult(

            stage_name=
                "Policy",

            success=True,

            message=
                "Policies evaluated.",

            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                *
                1000,
        )
