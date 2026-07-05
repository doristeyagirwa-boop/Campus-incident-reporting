class AssetImpactEngine:

    @staticmethod
    def calculate(
        asset,
    ):

        score = 0

        score += (
            asset.criticality_score
            * 0.5
        )

        score += (
            asset.risk_score
            * 0.5
        )

        return round(
            score,
            2,
        )
