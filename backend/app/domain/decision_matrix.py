from dataclasses import dataclass


@dataclass
class DecisionMatrix:

    risk_score: float

    priority: str

    approval_required: bool

    escalation_level: str

    governance_action: str

    compliance_required: list
