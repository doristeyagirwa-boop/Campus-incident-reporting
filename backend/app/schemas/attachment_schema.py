from datetime import datetime

from pydantic import BaseModel


class AttachmentResponseSchema(BaseModel):

    id: int

    incident_id: int

    filename: str

    file_size: int

    sha256_hash: str

    uploaded_by: str

    uploaded_at: datetime

    class Config:

        from_attributes = True
