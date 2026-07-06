class BusinessImpactEngine:

    @staticmethod
    def calculate(
        asset_score,
        location_score,
        affected_users,
    ):

        return round(

            (
                asset_score
                * 0.4
            )

            +

            (
                location_score
                * 0.3
            )

            +

            (
                affected_users
                * 0.3
            ),

            2,
        )
