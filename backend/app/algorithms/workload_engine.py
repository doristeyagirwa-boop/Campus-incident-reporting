class WorkloadEngine:

    def __init__(
        self,
        max_open_incidents=10,
    ):
        self.max_open_incidents=max_open_incidents

    @staticmethod
    def calculate_load(
        technician,
        incidents,
    ):

        return len(
            [
                incident
                for incident in incidents
                if (
                    incident.assigned_technician_id
                    ==
                    technician
                )
            ]
        )

    def can_accept(
        self,
        technician_id,
        incidents=None,
    ):

        incidents=incidents or []

        load=self.calculate_load(
            technician_id,
            incidents,
        )

        return (
            load
            <
            self.max_open_incidents
        )
