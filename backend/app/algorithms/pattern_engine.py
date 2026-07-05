from collections import Counter


class PatternEngine:

    @staticmethod
    def discover(
        incidents,
    ):

        categories = [
            incident.category
            for incident
            in incidents
        ]

        return Counter(
            categories
        ).most_common()
