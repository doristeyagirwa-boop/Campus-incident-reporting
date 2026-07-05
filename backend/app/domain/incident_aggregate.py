from dataclasses import dataclass

from app.models.incident import Incident


@dataclass
class IncidentAggregate:

    incident: Incident

    duplicate_candidates: list

    timeline: list

    attachments: list

    comments: list

    analytics: dict

    workflow_state: str

    risk_snapshot: dict

    sla_snapshot: dict
