class OperationalHealth:

    @staticmethod
    def calculate(
        active_incidents,
        sla_breaches,
        avg_risk,
    ):

        score = 100

        score -= (
            active_incidents * 0.5
        )

        score -= (
            sla_breaches * 2
        )

        score -= (
            avg_risk * 0.2
        )

        return max(
            round(score, 2),
            0,
        )
