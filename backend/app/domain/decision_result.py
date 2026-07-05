from dataclasses import dataclass


@dataclass
class DecisionResult:

    risk_score: float

    priority: str

    duplicate_candidates: list

    decisions: list

    assignment: object

    sla_snapshot: dict
