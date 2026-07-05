class DecisionRecommendationEngine:

    @staticmethod
    def recommend(
        risk_score,
        priority,
    ):

        if (
            risk_score > 80
            and priority == "P1"
        ):
            return (
                "EXECUTIVE_ESCALATION"
            )

        if (
            risk_score > 60
        ):
            return (
                "MANAGER_REVIEW"
            )

        return (
            "STANDARD_PROCESSING"
        )
