from app.ai.classification_engine import (
    ClassificationEngine,
)


class IncidentClassifier:

    @staticmethod
    def classify_incident(
        title,
        description,
    ):

        combined = (
            f"{title} {description}"
        )

        return (
            ClassificationEngine
            .classify(
                combined
            )
        )
