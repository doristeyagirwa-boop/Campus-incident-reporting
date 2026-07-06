from sqlalchemy import (
    String,
    Text,
    Integer,
    ForeignKey,
    Boolean,
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

class IncidentComment(
    UUIDMixin,
    TimestampMixin,
    Base,
):
    __tablename__ = "incident_comments"

    incident_id: Mapped[str] = mapped_column(
        ForeignKey("incidents.id"),
        nullable=False,
        index=True,
    )

    author_id: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
        index=True,
    )

    author_role: Mapped[str | None] = mapped_column(
        String(100),
        nullable=True,
    )

    comment_text: Mapped[str] = mapped_column(
        Text,
        nullable=False,
    )

    comment_type: Mapped[str] = mapped_column(
        String(50),
        nullable=False,
        default="COMMENT",
    )

    visibility: Mapped[str] = mapped_column(
        String(50),
        nullable=False,
        default="INTERNAL",
    )

    is_internal: Mapped[bool] = mapped_column(
        Boolean,
        nullable=False,
        default=True,
    )

    is_edited: Mapped[bool] = mapped_column(
        Boolean,
        nullable=False,
        default=False,
    )

    edit_count: Mapped[int] = mapped_column(
        Integer,
        nullable=False,
        default=0,
    )

    parent_comment_id: Mapped[str | None] = mapped_column(
        String(100),
        nullable=True,
    )

    ai_sentiment_score: Mapped[int | None] = mapped_column(
        Integer,
        nullable=True,
    )
