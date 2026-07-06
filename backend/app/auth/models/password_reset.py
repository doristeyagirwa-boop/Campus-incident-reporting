from sqlalchemy import (
    String,
    Boolean,
    DateTime,
    Text,
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


class PasswordReset(
    UUIDMixin,
    TimestampMixin,
    Base,
):
    __tablename__ = "password_resets"

    user_id: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
        index=True,
    )

    email: Mapped[str] = mapped_column(
        String(255),
        nullable=False,
        index=True,
    )

    reset_token: Mapped[str] = mapped_column(
        String(255),
        nullable=False,
        unique=True,
    )

    expires_at: Mapped[DateTime] = mapped_column(
        DateTime(timezone=True),
        nullable=False,
    )

    used: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
    )

    used_at: Mapped[DateTime] = mapped_column(
        DateTime(timezone=True),
        nullable=True,
    )

    requested_ip: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    requested_user_agent: Mapped[str] = mapped_column(
        Text,
        nullable=True,
    )
