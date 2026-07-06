from statistics import mean


class ResourceForecastEngine:

    @staticmethod
    def estimate_required_staff(
        historical_incident_counts,
        incidents_per_staff=20,
    ):

        if not historical_incident_counts:
            return 0

        expected = mean(
            historical_incident_counts
        )

        return round(
            expected
            /
            incidents_per_staff
        )
