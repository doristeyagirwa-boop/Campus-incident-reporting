class GovernanceEngine:

    @staticmethod
    def determine_action(
        risk_score,
        violations,
    ):

        violation_count = len(
            violations
        )

        if (
            risk_score >= 95
            and violation_count >= 3
        ):

            return {
                "action":
                    "EXECUTIVE_REVIEW",

                "freeze_changes":
                    True,

                "mandatory_audit":
                    True,
            }

        if risk_score >= 80:

            return {
                "action":
                    "MANAGER_REVIEW",

                "freeze_changes":
                    False,

                "mandatory_audit":
                    True,
            }

        return {
            "action":
                "STANDARD",

            "freeze_changes":
                False,

            "mandatory_audit":
                False,
        }
