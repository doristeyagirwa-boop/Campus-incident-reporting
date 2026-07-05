import time

from app.services.pipeline.pipeline_stage import PipelineStage

from app.domain.pipeline_context import PipelineContext
from app.domain.stage_result import StageResult

class RiskStage(PipelineStage):

    def __init__(
        self,
        risk_engine,
    ):
        self.risk_engine = (
            risk_engine
        )

    # Calculate operational risk

    def execute(
        self,
        context: PipelineContext,
    ) -> StageResult:

        started = (
            time.perf_counter()
        )

        context.risk_score = (
            self.risk_engine.calculate_risk(
                confidentiality=context.confidentiality,
                integrity=context.integrity,
                availability=context.availability,
                threat_severity=context.threat_severity,
                exposure_scope=context.exposure_scope,
            )
        )

        return StageResult(

            stage_name=
                "Risk",

            success=True,

            message=
                "Risk calculated.",

            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                *
                1000,
        )

    @staticmethod
    def calculate(context):

        return RiskEngine.calculate_risk(
            confidentiality=context.confidentiality,
            integrity=context.integrity,
            availability=context.availability,
            threat_severity=context.threat_severity,
            exposure_scope=context.exposure_scope,
        )

    @staticmethod
    def calculate_risk(
        confidentiality: int,
        integrity: int,
        availability: int,
        threat_severity: int,
        exposure_scope: int,
    ):

        risk_score = (
            confidentiality * 0.20
            + integrity * 0.20
            + availability * 0.20
            + threat_severity * 0.25
            + exposure_scope * 0.15
        )

        return round(
            risk_score * 10,
            2,
        )
