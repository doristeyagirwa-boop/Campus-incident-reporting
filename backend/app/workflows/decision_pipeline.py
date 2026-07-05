from app.services.orchestration.decision_coordinator import (
    DecisionCoordinator,
)


class DecisionPipeline:

    @staticmethod
    def execute(
        context,
    ):

        return (
            DecisionCoordinator
            .evaluate(
                category=
                    context.category,

                risk_score=
                    context.risk_score,

                priority=
                    context.priority,
            )
        )
