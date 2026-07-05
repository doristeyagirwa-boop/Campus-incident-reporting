from dataclasses import dataclass


@dataclass
class KnowledgeLink:

    incident_id: str

    article_id: str

    confidence_score: float

    matched_by: str
