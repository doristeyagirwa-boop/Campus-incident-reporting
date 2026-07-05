class TrendEngine:

    @staticmethod
    def detect(
        incidents,
    ):

        category_count = {}

        for incident in incidents:

            category_count[
                incident.category
            ] = (
                category_count.get(
                    incident.category,
                    0,
                )
                + 1
            )

        ranked = sorted(
            category_count.items(),
            key=lambda x: x[1],
            reverse=True,
        )

        return ranked
