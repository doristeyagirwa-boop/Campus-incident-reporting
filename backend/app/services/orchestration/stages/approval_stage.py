from app.algorithms.approval_engine import (
    ApprovalEngine,
)


class ApprovalStage:

    @staticmethod
    def execute(
        policy,
        risk_score,
    ):

        return (
            ApprovalEngine.required(
                policy,
                risk_score,
            )
        )
