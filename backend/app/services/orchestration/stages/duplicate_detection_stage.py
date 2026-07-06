from app.algorithms.similarity_engine import (
    SimilarityEngine,
)


class DuplicateDetectionStage:

    DUPLICATE_THRESHOLD = 85

    @classmethod
    def execute(
        cls,
        context,
        incidents,
    ):

        best_score = 0

        best_match = None

        for incident in incidents:

            score = (
                SimilarityEngine.compare(
                    context.title,
                    incident.title,
                )
            )

            if score > best_score:

                best_score = score

                best_match = incident

        context.similarity_score = (
            best_score
        )

        context.duplicate_found = (
            best_score
            >= cls.DUPLICATE_THRESHOLD
        )

        context.duplicate_incident = (
            best_match
        )

        return context
