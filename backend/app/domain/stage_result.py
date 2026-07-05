from dataclasses import dataclass
from dataclasses import field

@dataclass(slots=True)
class StageResult:

    stage_name: str
    success: bool
    message: str
    execution_time_ms: float = 0
    metadata: dict = field(default_factory=dict)
    warnings: list = field(default_factory=list)
    stop_pipeline: bool = False
