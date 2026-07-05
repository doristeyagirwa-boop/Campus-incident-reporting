from sqlalchemy import (
    String,
    BigInteger,
    Boolean,
    DateTime,
    Text,
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


class Attachment(
    UUIDMixin,
    TimestampMixin,
    Base,
):
    __tablename__ = "attachments"

    incident_id: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
        index=True,
    )

    original_filename: Mapped[str] = mapped_column(
        String(500),
        nullable=False,
    )

    stored_filename: Mapped[str] = mapped_column(
        String(500),
        nullable=False,
        unique=True,
    )

    storage_path: Mapped[str] = mapped_column(
        Text,
        nullable=False,
    )

    mime_type: Mapped[str] = mapped_column(
        String(255),
        nullable=False,
    )

    file_extension: Mapped[str] = mapped_column(
        String(20),
        nullable=True,
    )

    file_size: Mapped[int] = mapped_column(
        BigInteger,
        nullable=False,
    )

    sha256_hash: Mapped[str] = mapped_column(
        String(128),
        nullable=False,
        index=True,
    )

    uploaded_by: Mapped[str] = mapped_column(
        String(100),
        nullable=False,
    )

    uploaded_by_role: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    virus_scan_status: Mapped[str] = mapped_column(
        String(50),
        default="PENDING",
    )

    ai_processed: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
    )

    ai_summary: Mapped[str] = mapped_column(
        Text,
        nullable=True,
    )

    classification_label: Mapped[str] = mapped_column(
        String(100),
        nullable=True,
    )

    sensitivity_level: Mapped[str] = mapped_column(
        String(50),
        default="INTERNAL",
    )

    is_deleted: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
    )

    __table_args__ = (
        Index(
            "idx_attachment_incident",
            "incident_id",
        ),
        Index(
            "idx_attachment_hash",
            "sha256_hash",
        ),
    )
