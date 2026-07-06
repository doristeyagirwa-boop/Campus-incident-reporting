from app.algorithms.incident_number_generator import (
    IncidentNumberGenerator,
)

from app.algorithms.priority_engine import (
    PriorityEngine,
)

from app.algorithms.risk_engine import (
    RiskEngine,
)

from app.algorithms.similarity_engine import (
    SimilarityEngine,
)


def run():

    print(
        IncidentNumberGenerator.generate(
            "NETWORK",
            1,
        )
    )

    print(
        PriorityEngine.calculate_priority(
            severity_score=90,
            affected_users=100,
            risk_score=80,
            historical_frequency=10,
        )
    )

    print(
        RiskEngine.calculate_risk(
            10,
            9,
            8,
            10,
            8,
        )
    )

    print(
        SimilarityEngine.compare(
            "WiFi unavailable in LT4",
            "Wireless network unavailable in LT4",
        )
    )


if __name__ == "__main__":
    run()
