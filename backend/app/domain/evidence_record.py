from dataclasses import dataclass
from datetime import datetime


@dataclass
class EvidenceRecord:

    evidence_id: str

    attachment_id: str

    uploaded_by: str

    uploaded_at: datetime

    checksum: str

    verified: bool
