class ApprovalEngine:

    @staticmethod
    def required(
        policy,
        risk_score,
    ):

        threshold = (
            policy.get(
                "approval_threshold",
                90,
            )
        )

        return (
            risk_score
            >= threshold
        )

    @staticmethod
    def approval_chain(
        policy,
        risk_score,
    ):

        escalation_levels = (
            policy.get(
                "escalation_levels",
                {},
            )
        )

        chain = []

        for score in sorted(
            escalation_levels.keys()
        ):

            if risk_score >= score:

                chain.append(
                    escalation_levels[
                        score
                    ]
                )

        return chain
