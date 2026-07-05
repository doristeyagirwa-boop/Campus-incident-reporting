from dataclasses import dataclass


@dataclass
class ImpactProfile:

    operational_impact: int

    financial_impact: int

    compliance_impact: int

    reputational_impact: int

    safety_impact: int
