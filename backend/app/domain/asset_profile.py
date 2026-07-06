from dataclasses import dataclass


@dataclass
class AssetProfile:

    asset_id: str

    asset_name: str

    asset_type: str

    manufacturer: str

    model: str

    serial_number: str

    department: str

    location_id: str

    criticality_score: float

    risk_score: float

    lifecycle_stage: str

    maintenance_status: str

    operational_status: str
