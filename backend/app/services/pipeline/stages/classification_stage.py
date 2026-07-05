import time

from app.services.pipeline.pipeline_stage import PipelineStage

from app.domain.stage_result import StageResult

class ClassificationStage(PipelineStage):

    def __init__(
        self,
        classification_engine,
    ):
        self.classification_engine = classification_engine

    # Determine incident classification

    def execute(
        self,
        context,
    ):

        started = (
            time.perf_counter()
        )

        if (
            context.affected_users
            > 100
        ):

            context.classification = (
                "MAJOR"
            )

        else:

            context.classification = (
                "STANDARD"
            )

        return StageResult(

            stage_name=
                "Classification",

            success=True,

            message=
                "Classification completed.",

            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                *
                1000,
        )
