from dataclasses import dataclass

from datetime import datetime


@dataclass
class ResolutionRecord:

    incident_id: str

    resolution_summary: str

    resolved_by: str

    resolved_at: datetime

    root_cause: str

    corrective_action: str
