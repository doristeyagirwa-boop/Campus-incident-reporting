class LocationImpactEngine:

    @staticmethod
    def calculate(
        location,
    ):

        return round(
            (
                location
                .occupancy_capacity
                *
                location
                .operational_criticality
            ),
            2,
        )
