from datetime import datetime


class SLAMonitorEngine:

    @classmethod
    def evaluate(
        cls,
        incident,
    ):

        now = datetime.utcnow()

        response_breached = False

        resolution_breached = False

        if (
            incident.first_response_at
            is None
        ):

            response_breached = (
                now >
                incident.response_due_at
            )

        if (
            incident.status
            not in [
                "RESOLVED",
                "CLOSED",
            ]
        ):

            resolution_breached = (
                now >
                incident.resolution_due_at
            )

        return {

            "response_breached":
                response_breached,

            "resolution_breached":
                resolution_breached,
        }
