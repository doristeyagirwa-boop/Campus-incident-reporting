from dataclasses import dataclass


@dataclass
class OperationalTopology:

    topology_id: str

    asset_count: int

    location_count: int

    dependency_count: int

    risk_score: float

    operational_health: float
