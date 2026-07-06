from statistics import mean


class ForecastEngine:

    @staticmethod
    def estimate_next_period(
        historical_counts,
    ):

        if not historical_counts:

            return 0

        return round(
            mean(
                historical_counts
            )
        )
