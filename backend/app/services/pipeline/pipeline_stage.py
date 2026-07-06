from abc import ABC
from abc import abstractmethod

from app.domain.pipeline_context import (
    PipelineContext,
)

from app.domain.stage_result import (
    StageResult,
)


class PipelineStage(ABC):

    # Execute pipeline stage

    @abstractmethod
    def execute(
        self,
        context: PipelineContext,
    ) -> StageResult:
        pass
