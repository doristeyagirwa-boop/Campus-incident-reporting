from dataclasses import dataclass
from dataclasses import field

from app.domain.stage_result import StageResult

@dataclass(slots=True)
class PipelineResult:

    success: bool
    context: object
    execution_time_ms: float
    stages: list[StageResult] = field(default_factory=list)
    warnings: list[str] = field(default_factory=list)
    errors: list[str] = field(default_factory=list)
