from datetime import timedelta

from app.registry.sla_registry import SLA_REGISTRY

class SLAEngine:

    @classmethod
    def calculate(
        cls,
        priority,
        created_at,
    ):

        policy = (
            SLA_REGISTRY[
                priority
            ]
        )

        if not policy:

            policy = (
                SLA_REGISTRY[
                    "MEDIUM"
                ]
            )

        return {

            "response_due":
                created_at
                +
                timedelta(
                    hours=
                    policy.response_hours
                ),

            "resolution_due":
                created_at
                +
                timedelta(
                    hours=
                    policy.resolution_hours
                ),
        }
