from app.algorithms.sla_engine import SLAEngine

from app.algorithms.sla_monitor_engine import SLAMonitorEngine

class IncidentSLAOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    def evaluate_all(
        self,
    ):

        incidents = (
            self.context
            .incident_repository
            .get_open_incidents()
        )

        updated = []

        for incident in incidents:

            result = (
                SLAMonitorEngine
                .evaluate(
                    incident
                )
            )

            incident.sla_response_breached = (
                result[
                    "response_breached"
                ]
            )

            incident.sla_resolution_breached = (
                result[
                    "resolution_breached"
                ]
            )

            self.context\
                .incident_repository\
                .update(
                    incident
                )

            updated.append(
                incident
            )

        return updated
