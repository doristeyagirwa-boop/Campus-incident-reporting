class IncidentRetrievalOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    def get_incident(
        self,
        incident_id: int,
    ):

        incident = (
            self.context
            .incident_repository
            .get_by_id(
                incident_id
            )
        )

        if incident is None:

            raise ValueError(
                "Incident not found"
            )

        return incident
