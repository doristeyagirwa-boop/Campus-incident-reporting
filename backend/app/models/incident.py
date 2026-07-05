from datetime import datetime

from sqlalchemy import (
    Boolean,
    DateTime,
    Float,
    Index,
    Integer,
    String,
    Text,
    Column,
)

from sqlalchemy.orm import Mapped
from sqlalchemy.orm import mapped_column

from app.models.base import Base
from app.models.base import TimestampMixin
from app.models.base import UUIDMixin

from app.core.constants import IncidentPriority
from app.core.constants import IncidentStatus


class Incident(
    UUIDMixin,
    TimestampMixin,
    Base,
):

    __tablename__ = "incidents"

    incident_number: Mapped[str] = mapped_column(
        String(64),
        unique=True,
        nullable=False,
        index=True,
    )

    title: Mapped[str] = mapped_column(
        String(500),
        nullable=False,
    )

    description: Mapped[str] = mapped_column(
        Text,
        nullable=False,
    )

    category: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
        index=True,
    )

    location: Mapped[str] = mapped_column(
        String(255),
        nullable=True,
        index=True,
    )

    status: Mapped[str] = mapped_column(
        String(50),
        default=IncidentStatus.SUBMITTED.value,
        nullable=False,
        index=True,
    )

    priority: Mapped[str] = mapped_column(
        String(20),
        default=IncidentPriority.P4.value,
        nullable=False,
        index=True,
    )

    severity_score: Mapped[int] = mapped_column(
        Integer,
        default=0,
        nullable=False,
    )

    risk_score: Mapped[float] = mapped_column(
        Float,
        default=0.0,
        nullable=False,
    )

    similarity_score: Mapped[float] = mapped_column(
        Float,
        default=0.0,
        nullable=False,
    )

    escalation_level: Mapped[int] = mapped_column(
        Integer,
        default=0,
        nullable=False,
    )

    affected_users: Mapped[int] = mapped_column(
        Integer,
        default=1,
        nullable=False,
    )

    duplicate_count: Mapped[int] = mapped_column(
        Integer,
        default=0,
        nullable=False,
    )

    assigned_technician_id: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
        index=True,
    )

    reporter_id: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
        index=True,
    )

    is_duplicate: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
    )

    duplicate_of: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    first_response_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        nullable=True,
    )

    resolved_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        nullable=True,
    )

    closed_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        nullable=True,
    )

    deleted_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        nullable=True,
    )

    is_deleted: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
    )

    response_due_at = Column(
        DateTime,
        nullable=True,
    )

    resolution_due_at = Column(
        DateTime,
        nullable=True,
    )

    sla_response_breached = Column(
        Boolean,
        default=False,
    )

    sla_resolution_breached = Column(
        Boolean,
        default=False,
    )

    __table_args__ = (
        Index(
            "idx_incident_status_priority",
            "status",
            "priority",
        ),
        Index(
            "idx_incident_category_status",
            "category",
            "status",
        ),
        Index(
            "idx_incident_risk_score",
            "risk_score",
        ),
    )
