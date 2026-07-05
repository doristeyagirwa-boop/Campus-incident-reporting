from datetime import datetime

from app.knowledge.knowledge_article import (
    KnowledgeArticle,
)


class KnowledgeManager:

    def __init__(self):

        self.articles = {}

    def add_article(
        self,
        article_id,
        title,
        category,
        symptoms,
        root_causes,
        resolution_steps,
        preventive_actions,
    ):

        article = (
            KnowledgeArticle(
                article_id=article_id,
                title=title,
                category=category,
                symptoms=symptoms,
                root_causes=root_causes,
                resolution_steps=resolution_steps,
                preventive_actions=preventive_actions,
                created_at=datetime.utcnow(),
                updated_at=datetime.utcnow(),
            )
        )

        self.articles[
            article_id
        ] = article

        return article

    def get_article(
        self,
        article_id,
    ):

        return self.articles.get(
            article_id
        )
