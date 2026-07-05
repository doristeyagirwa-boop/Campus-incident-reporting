class AnalyticsCoordinator:

    def __init__(
        self,
        metrics_engine,
        forecast_engine,
        health_engine,
    ):
        self.metrics_engine = (
            metrics_engine
        )

        self.forecast_engine = (
            forecast_engine
        )

        self.health_engine = (
            health_engine
        )

    def build_dashboard(
        self,
        incidents,
    ):

        return {

            "metrics":
                self.metrics_engine
                .generate(
                    incidents
                ),

            "forecast":
                self.forecast_engine
                .forecast(
                    incidents
                ),

            "health":
                self.health_engine
                .evaluate(
                    incidents
                ),
        }
