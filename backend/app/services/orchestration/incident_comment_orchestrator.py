from uuid import UUID

from datetime import datetime

from app.models.incident_comment import IncidentComment
from app.models.incident_history import IncidentHistory

from app.domain.validation import IncidentValidator
from app.domain.exceptions import IncidentNotFound
from app.domain.events import CommentAddedEvent

class IncidentCommentOrchestrator:

    MAX_COMMENT_LENGTH = 5000
    MIN_COMMENT_LENGTH = 1

    def __init__(
        self,
        context,
    ):
        self.context = context

    def add_comment(
        self,
        incident_id: UUID,
        author: str,
        comment: str,
    ):

        incident = (
            self.context
            .incident_repository
            .get_by_id(
                incident_id
            )
        )

        if incident is None:
            raise ValueError(
                "Incident not found."
            )

        author = (
            IncidentValidator
            .validate_author(
                author
            )
        )

        comment = (
            IncidentValidator
            .validate_comment(
            comment
            )
        )

        comment_record = IncidentComment(
            incident_id=incident_id,
            author_id=author,
            comment=comment,
        )

        comment_record = (
            self.context
            .comment_repository
            .create(
                comment_record
            )
        )

        incident.updated_at = datetime.utcnow()

        self.context.incident_repository.update(
            incident
        )

        self.context.event_publisher.publish(
            CommentAddedEvent(
                occurred_at=datetime.utcnow(),
                incident_id=incident_id,
                author=author,
                comment=comment,
            )
        )

        return comment_record

    def get_comments(
        self,
        incident_id: UUID,
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

        return (
            self.context
            .comment_repository
            .get_comments(
                incident_id
            )
        )
