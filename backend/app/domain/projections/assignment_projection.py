from collections import defaultdict

class AssignmentProjection:

    def __init__(self):
        self.current={}
        self.history=defaultdict(list)

    def update(
        self,
        event,
    ):

        if (
            type(event).__name__
            !=
            "TechnicianAssignedEvent"
        ):
            return

        self.current[
            str(event.incident_id)
        ]=event.technician_id

        self.history[
            event.technician_id
        ].append(
            str(event.incident_id)
        )

    def technician_queue(
        self,
        technician,
    ):

        return self.history.get(
            technician,
            [],
        )
