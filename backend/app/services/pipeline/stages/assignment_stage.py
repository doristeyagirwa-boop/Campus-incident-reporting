import time

from app.services.pipeline.pipeline_stage import PipelineStage

from app.domain.pipeline_context import PipelineContext
from app.domain.stage_result import StageResult

class AssignmentStage(PipelineStage):

    def __init__(
        self,
        assignment_engine,
        technician_registry,
    ):

        self.assignment_engine = assignment_engine
        self.technician_registry = technician_registry

    # Select technician

    def execute(
        self,
        context: PipelineContext,
    ) -> StageResult:

        started = (
            time.perf_counter()
        )

        technicians = (
            self.technician_registry.all()
        )

        technician = (
            self.assignment_engine.assign(
                technicians,
                context,
            )
        )

        context.assigned_technician = technician

        return StageResult(

            stage_name=
                "Assignment",

            success=True,

            message=
                "Technician assigned.",

            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                *
                1000,
        )
