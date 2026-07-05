class CapacityEngine:

    @staticmethod
    def calculate_utilization(
        technicians,
    ):

        if not technicians:
            return 0

        total_capacity = sum(
            t.assignment_capacity
            for t in technicians
        )

        total_load = sum(
            t.active_incidents
            for t in technicians
        )

        if total_capacity == 0:
            return 0

        return round(
            (
                total_load
                / total_capacity
            ) * 100,
            2,
        )
