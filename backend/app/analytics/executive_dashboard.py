class ExecutiveDashboard:

    @staticmethod
    def generate_snapshot(
        metrics,
    ):

        return {

            "operational_health":
                "GREEN",

            "incident_volume":
                metrics.total_incidents,

            "risk_posture":
                metrics.average_risk_score,

            "open_workload":
                metrics.open_incidents,
        }
