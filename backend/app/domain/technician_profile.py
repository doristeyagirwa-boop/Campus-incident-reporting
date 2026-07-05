from dataclasses import dataclass


@dataclass
class TechnicianProfile:

    technician_id: str

    full_name: str

    department: str

    specialization: str

    skill_score: float

    certification_score: float

    workload_score: float

    active_incidents: int

    resolved_incidents: int

    sla_success_rate: float

    availability_score: float

    assignment_capacity: int
