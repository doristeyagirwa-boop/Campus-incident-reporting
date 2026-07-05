from dataclasses import dataclass
from dataclasses import field


@dataclass
class InstitutionalGraph:

    incidents: dict = field(
        default_factory=dict
    )

    assets: dict = field(
        default_factory=dict
    )

    departments: dict = field(
        default_factory=dict
    )

    technicians: dict = field(
        default_factory=dict
    )

    locations: dict = field(
        default_factory=dict
    )

    policies: dict = field(
        default_factory=dict
    )
