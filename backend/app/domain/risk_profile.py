from dataclasses import dataclass


@dataclass
class RiskProfile:

    profile_id: str

    profile_name: str

    category: str

    confidentiality_weight: float

    integrity_weight: float

    availability_weight: float

    compliance_weight: float

    financial_weight: float

    operational_weight: float

    reputation_weight: float

    escalation_threshold: float
