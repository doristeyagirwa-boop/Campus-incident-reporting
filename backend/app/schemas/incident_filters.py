from pydantic import BaseModel


class IncidentFilters(BaseModel):

    status: str | None = None

    priority: str | None = None

    category: str | None = None

    location: str | None = None

    reporter_id: str | None = None

    assigned_technician_id: str | None = None

    page: int = 1

    page_size: int = 20

    sort_by: str = "created_at"

    sort_direction: str = "desc"
