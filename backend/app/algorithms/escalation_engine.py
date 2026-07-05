from datetime import datetime

class EscalationEngine:

    @staticmethod
    def should_escalate(
        incident,
    ):

        if (
            incident.priority
            ==
            "CRITICAL"
        ):
            return True

        if (
            incident.risk_score
            >= 85
        ):
            return True

        return False
