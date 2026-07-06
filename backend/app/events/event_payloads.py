from dataclasses import dataclass


@dataclass
class IncidentCreatedEvent:

    incident_id: str

    incident_number: str

    priority: str

    risk_score: float


@dataclass
class IncidentAssignedEvent:

    incident_id: str

    technician_id: str


@dataclass
class IncidentClosedEvent:

    incident_id: str
