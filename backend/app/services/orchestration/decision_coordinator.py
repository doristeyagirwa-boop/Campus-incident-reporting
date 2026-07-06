from app.algorithms.policy_engine import (
    PolicyEngine,
)

from app.algorithms.approval_engine import (
    ApprovalEngine,
)

from app.algorithms.governance_engine import (
    GovernanceEngine,
)

from app.security.compliance_decision_engine import (
    ComplianceDecisionEngine,
)

from app.domain.decision_matrix import (
    DecisionMatrix,
)


class DecisionCoordinator:

    @staticmethod
    def evaluate(
        category,
        risk_score,
        priority,
    ):

        policy = (
            PolicyEngine.get_policy(
                category
            )
        )

        approval_required = (
            ApprovalEngine.required(
                policy,
                risk_score,
            )
        )

        compliance = (
            ComplianceDecisionEngine
            .evaluate(
                risk_score,
                policy,
            )
        )

        governance = (
            GovernanceEngine
            .determine_action(
                risk_score,
                compliance[
                    "required_reviews"
                ],
            )
        )

        escalation_level = (
            PolicyEngine
            .escalation_level(
                policy,
                risk_score,
            )
        )

        return DecisionMatrix(

            risk_score=
                risk_score,

            priority=
                priority,

            approval_required=
                approval_required,

            escalation_level=
                escalation_level,

            governance_action=
                governance["action"],

            compliance_required=
                compliance[
                    "required_reviews"
                ],
        )
