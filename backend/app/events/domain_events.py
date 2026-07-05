from dataclasses import dataclass
from datetime import datetime


@dataclass(slots=True)
class DomainEvent:

    event_type: str

    aggregate_id: str

    created_at: datetime

    occurred_at: datetime

    correlation_id: str | None = None

    request_id: str | None = None

    source_system: str = "WEB"

    actor_ip: str | None = None
