from dataclasses import dataclass

from typing import Optional

from app.repositories.incident_repository import IncidentRepository
from app.repositories.history_repository import HistoryRepository
from app.repositories.comment_repository import CommentRepository
from app.repositories.attachment_repository import AttachmentRepository
from app.repositories.audit_repository import AuditRepository

from app.domain.event_publisher import EventPublisher

from app.services.audit_service import AuditService
from app.services.unit_of_work import UnitOfWork

from app.registry.technician_registry import TechnicianRegistry
from app.registry.asset_registry import AssetRegistry
from app.registry.location_registry import LocationRegistry
from app.registry.policy_registry import PolicyRegistry
from app.registry.risk_profile_registry import RiskProfileRegistry
from app.registry.sla_registry import SLA_REGISTRY


@dataclass(slots=True)
class ServiceContext:

    # Repositories

    incident_repository: IncidentRepository
    history_repository: HistoryRepository
    comment_repository: CommentRepository
    attachment_repository: AttachmentRepository
    audit_repository: AuditRepository

    # Infrastructure

    audit_service: AuditService
    event_publisher: EventPublisher
    unit_of_work: UnitOfWork

    # Institutional Registries

    technician_registry: Optional[
        TechnicianRegistry
    ] = None

    asset_registry: Optional[
        AssetRegistry
    ] = None

    location_registry: Optional[
        LocationRegistry
    ] = None

    policy_registry: Optional[
        PolicyRegistry
    ] = None

    risk_profile_registry: Optional[
        RiskProfileRegistry
    ] = None

    sla_registry: Optional[
        dict
    ] = None
