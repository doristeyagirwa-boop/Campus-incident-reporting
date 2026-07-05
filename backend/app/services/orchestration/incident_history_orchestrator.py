class IncidentHistoryOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    def get_history(
        self,
        incident_id,
    ):

        incident = (
            self.context
            .incident_repository
            .get_by_id(
                incident_id
            )
        )

        if not incident:
            raise ValueError(
                "Incident not found"
            )

        return (
            self.context
            .history_repository
            .get_incident_history(
                incident_id
            )
        )
