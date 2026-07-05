from dataclasses import dataclass


@dataclass
class DepartmentProfile:

    department_id: str

    department_name: str

    active_incidents: int

    resolved_incidents: int

    average_resolution_hours: float

    average_risk_score: float

    sla_compliance_rate: float

    incident_volume_trend: float

    operational_health_score: float
