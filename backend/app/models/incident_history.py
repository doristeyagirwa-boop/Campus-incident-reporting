from sqlalchemy import (
    String,
    Text,
    Integer,
    DateTime,
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


class IncidentHistory(
    UUIDMixin,
    TimestampMixin,
    Base,
):
    __tablename__ = "incident_history"

    incident_id: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
        index=True,
    )

    event_type: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
        index=True,
    )

    field_name: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    old_value: Mapped[str] = mapped_column(
        Text,
        nullable=True,
    )

    new_value: Mapped[str] = mapped_column(
        Text,
        nullable=True,
    )

    change_reason: Mapped[str] = mapped_column(
        Text,
        nullable=True,
    )

    performed_by: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
        index=True,
    )

    performer_role: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    source_system: Mapped[str] = mapped_column(
        String(100),
        default="WEB",
    )

    session_id: Mapped[str] = mapped_column(
        String(255),
        nullable=True,
    )

    ip_address: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    workflow_state_before: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    workflow_state_after: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    risk_score_before: Mapped[int] = mapped_column(
        Integer,
        nullable=True,
    )

    risk_score_after: Mapped[int] = mapped_column(
        Integer,
        nullable=True,
    )

    is_system_generated: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
    )

    __table_args__ = (
        Index(
            "idx_history_incident_event",
            "incident_id",
            "event_type",
        ),
        Index(
            "idx_history_actor",
            "performed_by",
        ),
    )
