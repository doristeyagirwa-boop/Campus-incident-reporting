from dataclasses import dataclass
from typing import Any


@dataclass(slots=True)
class PolicyDecision:

    policy: dict[str, Any]

    requires_approval: bool

    escalation_level: str

    compliant: bool = True

    violated_policies: list[str] | None = None

    recommendations: list[str] | None = None

    audit_tags: list[str] | None = None

    governance_actions: list[str] | None = None
