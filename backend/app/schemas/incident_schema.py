from datetime import datetime

from pydantic import BaseModel
from pydantic import Field


class IncidentCreateSchema(BaseModel):

    title: str = Field(
        min_length=5,
        max_length=255,
    )

    description: str = Field(
        min_length=10,
        max_length=5000,
    )

    category: str

    location: str

    reporter_id: str

    affected_users: int = Field(
        ge=0,
        le=100000,
    )


class IncidentUpdateSchema(BaseModel):

    title: str | None = None

    description: str | None = None

    priority: str | None = None

    status: str | None = None

    assigned_technician_id: str | None = None


class IncidentResponseSchema(BaseModel):

    id: int

    incident_number: str

    title: str

    description: str

    category: str

    location: str

    reporter_id: str

    status: str

    priority: str

    risk_score: float

    assigned_technician_id: str | None

    created_at: datetime

    updated_at: datetime

    class Config:

        from_attributes = True
