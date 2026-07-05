class PriorityEngine:

    @staticmethod
    def calculate_priority(
        severity_score: int,
        affected_users: int,
        risk_score: float,
        historical_frequency: int,
    ):

        score = (
            severity_score * 0.40
            + affected_users * 0.20
            + risk_score * 0.30
            + historical_frequency * 0.10
        )

        if score >= 80:
            return "P1"

        if score >= 60:
            return "P2"

        if score >= 40:
            return "P3"

        return "P4"
