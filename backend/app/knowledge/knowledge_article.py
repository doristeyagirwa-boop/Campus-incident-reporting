from dataclasses import dataclass
from datetime import datetime


@dataclass
class KnowledgeArticle:

    article_id: str

    title: str

    category: str

    symptoms: list

    root_causes: list

    resolution_steps: list

    preventive_actions: list

    created_at: datetime

    updated_at: datetime

    incident_count: int = 0

    effectiveness_score: float = 0.0
