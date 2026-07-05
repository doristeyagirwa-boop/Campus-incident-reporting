from app.domain.sla_policy import (
    SLAPolicy,
)

SLA_REGISTRY = {

    "P1":
        SLAPolicy(
            priority="P1",
            response_hours=0.25,
            resolution_hours=4,
        ),

    "P2":
        SLAPolicy(
            priority="P2",
            response_hours=1,
            resolution_hours=8,
        ),

    "P3":
        SLAPolicy(
            priority="P3",
            response_hours=4,
            resolution_hours=24,
        ),

    "P4":
        SLAPolicy(
            priority="P4",
            response_hours=8,
            resolution_hours=72,
        ),
}
