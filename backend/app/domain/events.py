from dataclasses import dataclass
from datetime import datetime
from uuid import UUID

# Base Domain Event

@dataclass(slots=True)
class DomainEvent:

    occurred_at: datetime

# Incident Lifecycle

@dataclass(slots=True)
class IncidentCreatedEvent(DomainEvent):

    incident_id: UUID
    incident_number: str
    category: str
    reporter_id: str
    priority: str

@dataclass(slots=True)
class StatusChangedEvent(DomainEvent):

    incident_id: UUID
    actor: str
    old_status: str
    new_status: str

@dataclass(slots=True)
class IncidentEscalatedEvent(DomainEvent):

    incident_id: UUID
    actor: str
    escalation_level: str

@dataclass(slots=True)
class IncidentResolvedEvent(DomainEvent):

    incident_id: UUID
    actor: str

@dataclass(slots=True)
class IncidentClosedEvent(DomainEvent):

    incident_id: UUID
    actor: str

# Assignment

@dataclass(slots=True)
class TechnicianAssignedEvent(DomainEvent):

    incident_id: UUID
    technician_id: str
    actor: str
    previous_technician: str | None = None

# Comments

@dataclass(slots=True)
class CommentAddedEvent(DomainEvent):

    incident_id: UUID
    author: str
    comment: str

# Attachments

@dataclass(slots=True)
class AttachmentAddedEvent(DomainEvent):

    incident_id: UUID
    attachment_id: UUID
    filename: str
    actor: str

# Priority

@dataclass(slots=True)
class PriorityChangedEvent(DomainEvent):

    incident_id: UUID
    actor: str
    old_priority: str
    new_priority: str

# Risk

@dataclass(slots=True)
class RiskRecalculatedEvent(DomainEvent):

    incident_id: UUID
    previous_score: float
    new_score: float

# SLA

@dataclass(slots=True)
class SLAStartedEvent(DomainEvent):

    incident_id: UUID

@dataclass(slots=True)
class SLABreachedEvent(DomainEvent):

    incident_id: UUID
    breached_rule: str

@dataclass(slots=True)
class SLARecoveredEvent(DomainEvent):

    incident_id: UUID
