class AssignmentPolicy:

    def __init__(
        self,
        workload_engine,
    ):
        self.workload_engine=workload_engine

    def validate(
        self,
        incident,
        technician,
    ):

        if technician is None:
            raise ValueError(
                "Technician not found."
            )

        if not self.workload_engine.can_accept(
            technician.id,
        ):
            raise ValueError(
                "Technician workload exceeded."
            )

        return True
