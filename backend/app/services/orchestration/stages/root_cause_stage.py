from app.algorithms.root_cause_engine import (
    RootCauseEngine,
)


class RootCauseStage:

    def execute(
        self,
        context,
        **kwargs,
    ):

        result = (
            RootCauseEngine.analyze(
                context.description
            )
        )

        context.suggested_root_cause = (
            result
        )

        return context
