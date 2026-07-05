from app.security.compliance_decision_engine import (
    ComplianceDecisionEngine,
)


class ComplianceStage:

    @staticmethod
    def execute(
        risk_score,
        policy,
    ):

        return (
            ComplianceDecisionEngine
            .evaluate(
                risk_score,
                policy,
            )
        )
