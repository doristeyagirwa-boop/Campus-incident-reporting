from dataclasses import dataclass
from datetime import datetime


@dataclass
class EscalationRecord:

    incident_id: str

    escalation_level: int

    escalated_at: datetime

    reason: str

    escalated_by: str
