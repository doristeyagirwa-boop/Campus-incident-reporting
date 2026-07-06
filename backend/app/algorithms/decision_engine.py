class DecisionEngine:

    @staticmethod
    def evaluate_incident(
        risk_score,
        priority,
        duplicate_count,
    ):

        decisions = []

        if risk_score >= 90:

            decisions.append(
                "EXECUTIVE_REVIEW"
            )

        if priority == "P1":

            decisions.append(
                "IMMEDIATE_ASSIGNMENT"
            )

        if duplicate_count >= 5:

            decisions.append(
                "MAJOR_INCIDENT"
            )

        return decisions
