from dataclasses import dataclass
from dataclasses import field

from typing import Any

from app.domain.events import DomainEvent

@dataclass
class PipelineContext:

    title: str
    description: str
    category: str
    location: str
    reporter_id: str
    affected_users: int
    classification: str | None = None
    duplicate_found: bool = False
    duplicate_incident: Any = None
    similarity_score: float = 0
    risk_score: float = 0
    priority: str | None = None
    response_due_at: object | None = None
    resolution_due_at: object | None = None
    business_impact_score: float = 0
    assigned_technician: Any = None
    compliance_result: Any = None
    governance_result: Any = None
    approval_required: bool = False
    suggested_root_cause: str | None = None
    resolution_recommendation: str | None = None
    approval_chain: list[Any] = field(default_factory=list)
    recommendations: list[Any] = field(default_factory=list)
    evidence: list[Any] = field(default_factory=list)
    metadata: dict[str, Any] = field(default_factory=dict)
    incident: Any | None = None
    incident_number: str | None = None
    created_incident: Any | None = None
    workflow_state: str = "INITIAL"
    current_stage: str = ""
    events: list[DomainEvent] = field(default_factory=list)
    history: list[Any] = field(default_factory=list)
    audit: list[Any] = field(default_factory=list)
    execution_trace: list[Any] = field(default_factory=list)
    start_time: Any | None = None
    request_id: str | None = None
    correlation_id: str | None = None
    severity_score: int = 0
    historical_frequency: int = 0
    confidentiality: int = 5
    integrity: int = 5
    availability: int = 5
    threat_severity: int = 5
    exposure_scope: int = 5
