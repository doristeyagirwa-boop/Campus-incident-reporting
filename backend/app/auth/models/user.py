from sqlalchemy import Boolean
from sqlalchemy import ForeignKey
from sqlalchemy import String

from sqlalchemy.orm import Mapped
from sqlalchemy.orm import mapped_column

from app.models.base import Base
from app.models.base import TimestampMixin
from app.models.base import UUIDMixin

class User(
    UUIDMixin,
    TimestampMixin,
    Base,
):

    __tablename__ = "users"

    institution_id: Mapped[str] = mapped_column(
        String(50),
        unique=True,
        nullable=False,
        index=True,
    )

    email: Mapped[str] = mapped_column(
        String(255),
        unique=True,
        nullable=False,
        index=True,
    )

    password_hash: Mapped[str] = mapped_column(
        String(500),
        nullable=False,
    )

    first_name: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
    )

    last_name: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
    )

    role_id: Mapped[str] = mapped_column(
        ForeignKey("roles.id"),
        nullable=False,
    )

    is_active: Mapped[bool] = mapped_column(
        Boolean,
        default=True,
    )

    email_verified: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
    )

    failed_login_attempts: Mapped[int] = mapped_column(
        default=0,
    )

    account_locked: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
    )
