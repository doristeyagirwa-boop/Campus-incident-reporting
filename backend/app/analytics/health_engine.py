class HealthEngine:

    @staticmethod
    def calculate(
        incident,
    ):

        score = 100

        if incident.priority == "P1":
            score -= 30

        if incident.status != "CLOSED":
            score -= 20

        if incident.risk_score > 80:
            score -= 30

        return max(
            score,
            0,
        )
