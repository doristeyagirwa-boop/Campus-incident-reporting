class ComplianceEngine:

    @staticmethod
    def validate_incident(
        incident,
    ):

        violations = []

        if (
            not incident.reporter_id
        ):
            violations.append(
                "Missing reporter"
            )

        if (
            not incident.category
        ):
            violations.append(
                "Missing category"
            )

        return violations
