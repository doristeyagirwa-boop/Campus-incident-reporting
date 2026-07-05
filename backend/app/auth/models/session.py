from sqlalchemy import (
    String,
    Boolean,
    DateTime,
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


class Session(
    UUIDMixin,
    TimestampMixin,
    Base,
):
    __tablename__ = "sessions"

    user_id: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
        index=True,
    )

    session_token: Mapped[str] = mapped_column(
        String(512),
        nullable=False,
        unique=True,
    )

    device_name: Mapped[str] = mapped_column(
        String(255),
        nullable=True,
    )

    ip_address: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    user_agent: Mapped[str] = mapped_column(
        String(1000),
        nullable=True,
    )

    expires_at: Mapped[DateTime] = mapped_column(
        DateTime(timezone=True),
        nullable=False,
    )

    revoked: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
    )
