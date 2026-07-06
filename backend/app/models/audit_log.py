from sqlalchemy import (
    String,
    Text,
    Boolean,
    Index,
)

from sqlalchemy.orm import (
    Mapped,
    mapped_column,
)

from app.models.base import (
    Base,
    UUIDMixin,
    TimestampMixin,
)


class AuditLog(

    UUIDMixin,

    TimestampMixin,

    Base,
):

    __tablename__ = "audit_logs"

    incident_id: Mapped[str] = mapped_column(

        String(100),

        nullable=True,

        index=True,
    )

    event_type: Mapped[str] = mapped_column(

        String(100),

        nullable=False,

        index=True,
    )

    actor: Mapped[str] = mapped_column(

        String(100),

        nullable=False,

        index=True,
    )

    source_system: Mapped[str] = mapped_column(

        String(100),

        default="WEB",
    )

    details: Mapped[str] = mapped_column(

        Text,

        nullable=True,
    )

    request_id: Mapped[str] = mapped_column(

        String(255),

        nullable=True,
    )

    correlation_id: Mapped[str] = mapped_column(

        String(255),

        nullable=True,
    )

    session_id: Mapped[str] = mapped_column(

        String(255),

        nullable=True,
    )

    ip_address: Mapped[str] = mapped_column(

        String(100),

        nullable=True,
    )

    service_name: Mapped[str] = mapped_column(

        String(150),

        default="IncidentReportModule",
    )

    is_success: Mapped[bool] = mapped_column(

        Boolean,

        default=True,
    )

    __table_args__ = (

        Index(
            "idx_audit_actor",
            "actor",
        ),

        Index(
            "idx_audit_event",
            "event_type",
        ),

        Index(
            "idx_audit_incident",
            "incident_id",
        ),
    )
