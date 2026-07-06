from datetime import datetime

from app.models.attachment import Attachment

from app.domain.events import AttachmentAddedEvent
from app.domain.exceptions import IncidentNotFound

class IncidentAttachmentOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    # Add attachment

    def add_attachment(
        self,
        incident_id,
        filename,
        storage_path,
        uploaded_by,
    ):

        incident = (
            self.context
            .incident_repository
            .get_by_id(
                incident_id
            )
        )

        if incident is None:
            raise IncidentNotFound(
                "Incident not found."
            )

        attachment = Attachment(
            incident_id=
                incident.id,

            filename=
                filename,

            storage_path=
                storage_path,

            uploaded_by=
                uploaded_by,

        )

        attachment = (
            self.context
            .attachment_repository
            .create(
                attachment
            )

        )

        incident.updated_at = (
            datetime.utcnow()
        )

        self.context.incident_repository.update(
            incident
        )

        self.context.event_publisher.publish(

            AttachmentAddedEvent(

                occurred_at=
                    datetime.utcnow(),

                incident_id=
                    incident.id,

                actor=
                    uploaded_by,

                attachment_id=
                    attachment.id,

                filename=
                    filename,

            )

        )

        return attachment
