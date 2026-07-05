class WorkloadEngine:

    def current_load(
        self,
        technician_id,
    ):

        return 0

    def can_accept(
        self,
        technician_id,
        limit=15,
    ):

        return (

            self.current_load(
                technician_id
            )
            <
            limit
        )
