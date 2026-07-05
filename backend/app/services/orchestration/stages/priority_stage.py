from app.algorithms.priority_engine import (
    PriorityEngine,
)


class PriorityStage:

    @staticmethod
    def execute(
        context,
	**kwargs,
    ):

        context.priority = (
            PriorityEngine.calculate_priority(
                severity_score=
                    context.risk_score,

                affected_users=
                    context.affected_users,

                risk_score=
                    context.risk_score,

                historical_frequency=
                    5,
            )
        )

        return context
