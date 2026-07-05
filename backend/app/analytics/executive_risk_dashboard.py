class ExecutiveRiskDashboard:

    @staticmethod
    def summarize(
        incidents,
    ):

        total = len(
            incidents
        )

        critical = len(
            [
                i
                for i in incidents
                if i.risk_score >= 80
            ]
        )

        return {
            "total": total,
            "critical": critical,
        }
