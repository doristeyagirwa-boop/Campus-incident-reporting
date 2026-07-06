
class RiskEngine:

    @staticmethod
    def calculate_risk(
        confidentiality: int,
        integrity: int,
        availability: int,
        threat_severity: int,
        exposure_scope: int,
    ):

        risk_score = (
            confidentiality * 0.20
            + integrity * 0.20
            + availability * 0.20
            + threat_severity * 0.25
            + exposure_scope * 0.15
        )

        return round(
            risk_score * 10,
            2,
        )
