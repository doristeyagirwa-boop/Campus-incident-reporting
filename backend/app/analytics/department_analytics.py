from collections import defaultdict


class DepartmentAnalytics:

    @staticmethod
    def calculate(
        incidents,
    ):

        stats = defaultdict(int)

        for incident in incidents:

            department = getattr(
                incident,
                "department",
                "UNKNOWN",
            )

            stats[department] += 1

        return dict(stats)
