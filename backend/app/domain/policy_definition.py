from dataclasses import dataclass


@dataclass
class PolicyDefinition:

    policy_id: str

    policy_name: str

    category: str

    severity_level: str

    approval_required: bool

    automatic_escalation: bool

    mandatory_documentation: bool

    sla_hours: int

    enabled: bool = True
