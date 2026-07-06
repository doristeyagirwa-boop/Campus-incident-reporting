import time

from app.services.pipeline.pipeline_stage import PipelineStage

from app.domain.stage_result import StageResult

class DuplicateDetectionStage(PipelineStage):

    def __init__(
        self,
        repository,
        similarity_engine,
    ):

        self.repository = (
            repository
        )

        self.similarity_engine = (
            similarity_engine
        )

    # Search for duplicate incidents

    def execute(
        self,
        context,
    ):

        started = (
            time.perf_counter()
        )

        duplicate = (
            self.repository
            .find_possible_duplicate(
                context.title,
                context.description,
            )
        )

        if duplicate:

            context.duplicate_found = (
                True
            )

            context.duplicate_incident = (
                duplicate
            )

        return StageResult(

            stage_name=
                "Duplicate Detection",

            success=True,

            message=
                "Duplicate analysis completed.",

            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                *
                1000,
        )
