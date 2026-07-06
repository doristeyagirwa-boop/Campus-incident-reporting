import time

from app.domain.pipeline_result import (
    PipelineResult,
)


class PipelineEngine:

    def __init__(
        self,
    ):

        self.stages = []

    def add_stage(
        self,
        stage,
    ):

        self.stages.append(
            stage
        )

    def execute(
        self,
        context,
        **kwargs,
    ):

        start = (
            time.perf_counter()
        )

        current_stage = (
            "INITIALIZATION"
        )

        try:

            for stage in (
                self.stages
            ):

                current_stage = (
                    stage.__name__
                )

                context = (
                    stage.execute(
                        context,
                        **kwargs,
                    )
                )

            duration = (
                (
                    time.perf_counter()
                    - start
                )
                * 1000
            )

            return PipelineResult(

                success=True,

                stage=current_stage,

                message=
                    "PIPELINE_SUCCESS",

                context=context,

                execution_time_ms=
                    duration,
            )

        except Exception as exc:

            duration = (
                (
                    time.perf_counter()
                    - start
                )
                * 1000
            )

            return PipelineResult(

                success=False,

                stage=current_stage,

                message=str(exc),

                context=context,

                execution_time_ms=
                    duration,
            )
