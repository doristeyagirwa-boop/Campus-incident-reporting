from app.analytics.metrics_engine import MetricsEngine


class IncidentAnalyticsOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    def get_metrics(
        self,
    ):

        incidents = (
            self.context
            .incident_repository
            .get_all()
        )

        return (
            MetricsEngine.calculate(
                incidents
            )
        )

    def list_incidents(
        self,
        filters,
    ):

        incidents, total = (
            self.context
            .incident_repository
            .search(
                filters
            )
        )

        return {
            "items": incidents,
            "total": total,
            "page": filters.page,
            "page_size": filters.page_size,
        }
