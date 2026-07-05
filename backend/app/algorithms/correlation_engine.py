from collections import defaultdict


class CorrelationEngine:

    @staticmethod
    def correlate(
        incidents,
    ):

        categories = (
            defaultdict(int)
        )

        locations = (
            defaultdict(int)
        )

        for incident in incidents:

            categories[
                incident.category
            ] += 1

            locations[
                incident.location
            ] += 1

        return {
            "categories":
                dict(categories),
            "locations":
                dict(locations),
        }


    @staticmethod
    def calculate(
        incident,
        existing_incidents,
    ):

        matches = []

        for existing in existing_incidents:

            if (
                existing.category
                ==
                incident.category
            ):
                matches.append(
                    existing
                )

        return matches
