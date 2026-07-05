from dataclasses import dataclass
from datetime import datetime
from uuid import UUID

from app.domain.events import DomainEvent

@dataclass
class TechnicianAssignedEvent(DomainEvent):

    incident_id:UUID
    actor:str
    previous_technician:str|None
    technician_id:str
    assignment_reason:str
