from dataclasses import dataclass


@dataclass
class OrchestrationResult:

    incident_id: str

    incident_number: str

    risk_score: float

    priority: str

    business_impact: float

    assigned_technician: str | None

    governance_action: str

    approval_required: bool
