from sqlalchemy.orm import Session

from app.models.attachment import (
    Attachment,
)


class AttachmentRepository:

    def __init__(
        self,
        db: Session,
    ):
        self.db = db

    def create(
        self,
        attachment: Attachment,
    ):

        self.db.add(attachment)

        self.db.commit()

        self.db.refresh(
            attachment
        )

        return attachment

    def get_incident_attachments(
        self,
        incident_id: str,
    ):

        return (
            self.db.query(
                Attachment
            )
            .filter(
                Attachment.incident_id
                == incident_id
            )
            .all()
        )
