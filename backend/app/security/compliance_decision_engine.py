class ComplianceDecisionEngine:

    @staticmethod
    def evaluate(
        risk_score,
        policy,
    ):

        frameworks = (
            policy.get(
                "compliance_frameworks",
                [],
            )
        )

        required_reviews = []

        if risk_score >= 70:

            required_reviews.extend(
                frameworks
            )

        return {

            "frameworks":
                frameworks,

            "required_reviews":
                required_reviews,

            "compliant":
                len(
                    required_reviews
                )
                == 0,
        }
