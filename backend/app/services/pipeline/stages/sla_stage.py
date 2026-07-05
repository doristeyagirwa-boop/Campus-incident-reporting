from datetime import datetime

from app.algorithms.sla_engine import SLAEngine

from app.domain.stage_result import StageResult

from app.services.pipeline.pipeline_stage import PipelineStage

class SLAStage(PipelineStage):

    def __init__(
        self,
        sla_engine,
    ):
        self.sla_engine = sla_engine

    # Calculate SLA deadlines.

    def execute(
        self,
        context,
    ):

        sla = SLAEngine.calculate(
            context.priority,
            datetime.utcnow(),
        )

        context.response_due_at = (
            sla["response_due"]
        )

        context.resolution_due_at = (
            sla["resolution_due"]
        )

        return StageResult(
            stage_name= "SLA Calculation",
            success= True,
            message= "SLA calculated.",
        )
