from app.ai.classification_engine import (
    ClassificationEngine,
)

class ClassificationStage:

    @staticmethod
    def execute(
        context,
        **kwargs,
    ):

        combined = (
            f"{context.title} "
            f"{context.description}"
        )

        context.category = (
            ClassificationEngine.classify(
                combined
            )
        )

        return context
