from dataclasses import dataclass
from dataclasses import field


@dataclass(slots=True)
class DecisionPipeline:

    stages: list = field(
        default_factory=list
    )

    stage_results: list = field(
        default_factory=list
    )

    decisions: list = field(
        default_factory=list
    )

    warnings: list = field(
        default_factory=list
    )

    recommendations: list = field(
        default_factory=list
    )

    risk_score: float = 0

    priority: str = "P4"

    assigned_to: str | None = None

    approved: bool = False
