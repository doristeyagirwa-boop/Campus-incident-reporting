from app.domain.incident_timeline import (
    IncidentTimeline,
)


class IncidentTimelineOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    def get_timeline(
        self,
        incident_id,
    ):

        return (
            self.context
            .history_repository
            .get_incident_timeline(
                incident_id
            )
        )

    def build_timeline(
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

        history = (
            self.context
            .history_repository
            .get_incident_history(
                incident_id
            )
        )

        timeline = []

        timeline.append({
            "event":
                "CREATED",

            "timestamp":
                incident.created_at,
        })

        for event in history:

            timeline.append({

                "event":
                    event.event_type,

                "old":
                    event.old_value,

                "new":
                    event.new_value,

                "performed_by":
                    event.performed_by,

                "timestamp":
                    event.created_at,
            })

        return timeline
