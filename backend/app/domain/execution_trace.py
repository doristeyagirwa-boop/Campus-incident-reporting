from dataclasses import dataclass
from dataclasses import field


@dataclass
class ExecutionTrace:

    incident_number: str

    stages: list = field(
        default_factory=list
    )

    decisions: list = field(
        default_factory=list
    )

    risk_factors: list = field(
        default_factory=list
    )

    recommendations: list = field(
        default_factory=list
    )

    compliance_checks: list = field(
        default_factory=list
    )
