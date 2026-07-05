from datetime import datetime
import time

from app.services.pipeline.pipeline_stage import PipelineStage

from app.domain.pipeline_context import PipelineContext
from app.domain.stage_result import StageResult
from app.domain.events import IncidentCreatedEvent

class EventStage(PipelineStage):

    def __init__(
        self,
        publisher,
    ):

        self.publisher = publisher

    # Publish accumulated domain events.

    def execute(
        self,
        context,
    ):

        started = (
            time.perf_counter()
        )

        for event in context.events:
            self.publisher.publish(
                event
            )

        return StageResult(

            stage_name=
                "Event",

            success=True,

            message=
                f"{len(context.events)} event(s) published.",

            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                *
                1000,
        )
