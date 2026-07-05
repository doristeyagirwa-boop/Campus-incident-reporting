from app.security.compliance_engine import (
    ComplianceEngine,
)


class IncidentComplianceOrchestrator:

    def __init__(
        self,
        context,
    ):
        self.context = context

    def validate(
        self,
        incident,
    ):

        violations = (
            ComplianceEngine.validate_incident(
                incident
            )
        )

        if violations:

            self.context.history_repository.create_event(
                incident_id=
                    incident.id,

                event_type=
                    "COMPLIANCE_FAILURE",

                performed_by=
                    "SYSTEM",

                field_name=
                    "compliance",

                old_value=
                    "",

                new_value=
                    ",".join(
                        violations
                    ),
            )

        return violations
