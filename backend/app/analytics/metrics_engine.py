from statistics import mean

from app.analytics.incident_metrics import IncidentMetrics

class MetricsEngine:

    @classmethod
    def calculate(
        cls,
        incidents,
    ):

        total = len(
            incidents
        )

        open_count = 0

        assigned_count = 0

        escalated_count = 0

        resolved_count = 0

        closed_count = 0

        critical_count = 0

        sla_breach_count = 0

        for incident in incidents:

            if incident.status in [

                "SUBMITTED",

                "ASSIGNED",

                "INVESTIGATING",

            ]:

                open_count += 1

            if (
                incident.status
                ==
                "ASSIGNED"
            ):
                assigned_count += 1

            if (
                incident.status
                ==
                "ESCALATED"
            ):
                escalated_count += 1

            if (
                incident.status
                ==
                "RESOLVED"
            ):
                resolved_count += 1

            if (
                incident.status
                ==
                "CLOSED"
            ):
                closed_count += 1

            if (
                incident.priority
                ==
                "CRITICAL"
            ):
                critical_count += 1

            if (

                incident.sla_response_breached

                or

                incident.sla_resolution_breached

            ):

                sla_breach_count += 1

            risk_scores = [
                incident.risk_score
                for incident in incidents
                if incident.risk_score is not None
            ]

            average_risk_score = (
                mean(risk_scores)
                if risk_scores
                else 0.0
            )

            average_resolution_time = 0.0

        return IncidentMetrics(

            total_incidents=
                total,

            open_incidents=
                open_count,

            assigned_incidents=
                assigned_count,

            escalated_incidents=
                escalated_count,

            resolved_incidents=
                resolved_count,

            closed_incidents=
                closed_count,

            critical_incidents=
                critical_count,

            sla_breaches=
                sla_breach_count,

            average_risk_score=
                average_risk_score,

            average_resolution_time=
                average_resolution_time,
        )
