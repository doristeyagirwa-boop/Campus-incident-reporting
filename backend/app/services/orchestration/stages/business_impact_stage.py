from app.algorithms.business_impact_engine import (
    BusinessImpactEngine,
)

from app.algorithms.asset_impact_engine import (
    AssetImpactEngine,
)

from app.algorithms.location_impact_engine import (
    LocationImpactEngine,
)


class BusinessImpactStage:

    @staticmethod
    def execute(
        asset,
        location,
        affected_users,
    ):

        asset_score = 0

        location_score = 0

        if asset:

            asset_score = (
                AssetImpactEngine
                .calculate(asset)
            )

        if location:

            location_score = (
                LocationImpactEngine
                .calculate(location)
            )

        return (
            BusinessImpactEngine
            .calculate(
                asset_score=
                    asset_score,

                location_score=
                    location_score,

                affected_users=
                    affected_users,
            )
        )
