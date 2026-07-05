from app.analytics.metrics_engine import (
    MetricsEngine,
)

from app.analytics.executive_dashboard import (
    ExecutiveDashboard,
)


class AnalyticsAggregator:

    @staticmethod
    def generate(
        incidents,
        metrics,
    ):

        return {

            "average_risk":
                MetricsEngine.average_risk(
                    incidents
                ),

            "priority_distribution":
                MetricsEngine.priority_distribution(
                    incidents
                ),

            "executive_dashboard":
                ExecutiveDashboard.generate_snapshot(
                    metrics
                ),
        }
