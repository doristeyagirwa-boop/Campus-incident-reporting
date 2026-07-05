class PolicyEngine:

    @classmethod
    def validate_creation(
        cls,
        policy,
        reporter_id,
    ):

        if not policy:

            raise ValueError(
                "No matching policy."
            )

        if not reporter_id:

            raise ValueError(
                "Reporter required"
            )

        return policy

    @classmethod
    def requires_approval(
        cls,
        policy,
        risk_score,
    ):

        threshold = (
            policy.get(
                "approval_threshold",
                75,
            )
        )

        return (
            risk_score
            >= threshold
        )

    @classmethod
    def escalation_level(
        cls,
        policy,
        risk_score,
    ):

        levels = (
            policy.get(
                "escalation_levels",
                {},
            )
        )

        for score in sorted(
            levels.keys(),
            reverse=True,
        ):

            if risk_score >= score:

                return levels[score]

        return "NORMAL"
