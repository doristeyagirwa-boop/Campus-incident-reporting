from app.algorithms.governance_engine import (
    GovernanceEngine,
)


class GovernanceStage:

    @staticmethod
    def execute(
        risk_score,
        violations,
    ):

        return (
            GovernanceEngine
            .determine_action(
                risk_score,
                violations,
            )
        )
