from dataclasses import dataclass


@dataclass
class IncidentMetrics:

    total_incidents: int

    open_incidents: int

    assigned_incidents: int

    escalated_incidents: int

    resolved_incidents: int

    closed_incidents: int

    average_risk_score: float

    average_resolution_time: float

    critical_incidents: int

    sla_breaches: int
