from datetime import datetime
from datetime import timedelta

from sqlalchemy import func
from sqlalchemy.orm import Session

from app.models.incident_comment import IncidentComment

class CommentRepository:

    def __init__(
        self,
        db: Session,
    ):
        self.db = db

    def create(
        self,
        comment: IncidentComment,
    ):

        self.db.add(comment)

        self.db.flush()

        self.db.refresh(comment)

        return comment

    def get_comments(
        self,
        incident_id: str,
    ):

        return (
            self.db.query(
                IncidentComment
            )
            .filter(
                IncidentComment.incident_id
                == incident_id
            )
            .order_by(
                IncidentComment.created_at.asc()
            )
            .all()
        )

        def count_comments(
            self,
            incident_id,
        ):

            return (
                self.db.query(
                    func.count(
                        IncidentComment.id
                    )
                )
                .filter(
                    IncidentComment.incident_id
                    == incident_id
                )
                .scalar()
            )

        def latest_comment(
            self,
            incident_id,
        ):

            return (
                self.db.query(
                    IncidentComment
                )
                .filter(
                    IncidentComment.incident_id
                    == incident_id
                )
                .order_by(
                    IncidentComment.created_at.desc()
                )
                .first()
            )

        def comments_after(
            self,
            incident_id,
            timestamp,
        ):

            return (
                self.db.query(
                    IncidentComment
                )
                .filter(
                    IncidentComment.incident_id
                    == incident_id,
                    IncidentComment.created_at >= timestamp,
                )
                .all()
            )

        def comments_by_author(
            self,
            author,
        ):

            return (
                self.db.query(
                    IncidentComment
                )
                .filter(
                    IncidentComment.author_id
                    == author
                )
                .all()
            )

        def has_comments(
            self,
            incident_id,
        ):

            return (
                self.count_comments(
                    incident_id
                )
                > 0
            )
