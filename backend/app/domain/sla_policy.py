from dataclasses import dataclass


@dataclass
class SLAPolicy:

    priority: str

    response_hours: int

    resolution_hours: int
