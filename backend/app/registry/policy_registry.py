from app.algorithms.policy_engine import PolicyEngine

from app.domain.policy_decision import PolicyDecision

class PolicyRegistry:

    POLICY_REGISTRY = {

        "NETWORK": {
            "approval_threshold": 80,
            "escalation_levels": {
                95: "CIO",
                85: "DIRECTOR_IT",
                70: "NOC_MANAGER",
            },

            "compliance_frameworks": [
                "ISO27001",
                "ITIL",
            ],
        },

        "SECURITY": {
            "approval_threshold": 60,
            "escalation_levels": {
                95: "CISO",
                85: "SOC_MANAGER",
                70: "SECURITY_LEAD",
            },
            "compliance_frameworks": [
                "ISO27001",
                "NIST",
                "CIS",
            ],
        },

        "GENERAL": {
            "approval_threshold": 95,
            "escalation_levels": {
                90: "OPERATIONS_MANAGER",
            },
            "compliance_frameworks": [],
        },
    }

    def evaluate(
        self,
        category,
        reporter_id,
        priority,
        risk_score,
    ):

        policy = (
            self.POLICY_REGISTRY.get(
                category,
                self.POLICY_REGISTRY["GENERAL"],
            )
        )

        policy = (
            PolicyEngine.validate_creation(
                policy=policy,
                reporter_id=reporter_id,
            )
        )

        return PolicyDecision(

            policy=policy,

            requires_approval=
                PolicyEngine.requires_approval(
                    policy,
                    risk_score,
                ),

            escalation_level=
                PolicyEngine.escalation_level(
                    policy,
                    risk_score,
                ),

            compliant=True,
        )
