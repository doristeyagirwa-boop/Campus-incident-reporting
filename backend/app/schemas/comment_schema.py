from datetime import datetime
from uuid import UUID

from pydantic import BaseModel
from pydantic import ConfigDict


class CommentCreateSchema(BaseModel):

    comment: str


class CommentResponseSchema(BaseModel):

    id: UUID
    incident_id: UUID
    author_id: str
    comment_text: str
    created_at: datetime

    model_config = ConfigDict(
        from_attributes=True,
    )
