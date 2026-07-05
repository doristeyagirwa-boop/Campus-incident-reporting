from app.algorithms.risk_engine import (
    RiskEngine,
)


class RiskStage:

    @staticmethod
    def execute(
        context,
	**kwargs,
    ):

        context.risk_score = (
            RiskEngine.calculate_risk(
                confidentiality=5,
                integrity=5,
                availability=5,
                threat_severity=min(
                    10,
                    max(
                        1,
                        context.affected_users // 25
                    ),
                ),
                exposure_scope=min(
                    10,
                    max(
                        1,
                        context.affected_users // 10
                    ),
                ),
            )
        )

        return context
