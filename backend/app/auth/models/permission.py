from sqlalchemy import String
from sqlalchemy.orm import Mapped
from sqlalchemy.orm import mapped_column

from app.models.base import Base
from app.models.base import UUIDMixin
from app.models.base import TimestampMixin

class Permission(
    UUIDMixin,
    TimestampMixin,
    Base,
):

    __tablename__ = "permissions"

    name: Mapped[str] = mapped_column(
        String(150),
        unique=True,
        nullable=False,
        index=True,
    )

    description: Mapped[str] = mapped_column(
        String(500),
        nullable=True,
    )
