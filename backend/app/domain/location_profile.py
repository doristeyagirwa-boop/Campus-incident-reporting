from dataclasses import dataclass


@dataclass
class LocationProfile:

    location_id: str

    location_name: str

    building: str

    floor: str

    room: str

    campus: str

    department: str

    occupancy_capacity: int

    operational_criticality: float
