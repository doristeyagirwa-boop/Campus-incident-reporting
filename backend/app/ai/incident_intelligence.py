from app.algorithms.risk_engine import (
    RiskEngine,
)

from app.algorithms.priority_engine import (
    PriorityEngine,
)

from app.algorithms.similarity_engine import (
    SimilarityEngine,
)

from app.algorithms.assignment_engine import (
    AssignmentEngine,
)

from app.algorithms.sla_engine import (
    SLAEngine,
)

from app.algorithms.decision_engine import (
    DecisionEngine,
)

from app.domain.decision_result import (
    DecisionResult,
)


class IncidentIntelligence:

    @classmethod
    def evaluate(
        cls,
        title,
        description,
        affected_users,
        category,
        incidents,
        created_at,
    ):

        duplicate_candidates = []

        for incident in incidents:

            score = (
                SimilarityEngine.compare(
                    title,
                    incident.title,
                )
            )

            if score >= 80:

                duplicate_candidates.append(
                    {
                        "incident":
                            incident,
                        "score":
                            score,
                    }
                )

        risk_score = (
            RiskEngine.calculate_risk(
                confidentiality=5,
                integrity=5,
                availability=5,
                threat_severity=5,
                exposure_scope=5,
            )
        )

        priority = (
            PriorityEngine.calculate_priority(
                severity_score=50,
                affected_users=affected_users,
                risk_score=risk_score,
                historical_frequency=5,
            )
        )

        assignment = (
            AssignmentEngine.assign(
                category
            )
        )

        sla_snapshot = (
            SLAEngine.calculate_targets(
                priority,
                created_at,
            )
        )

        decisions = (
            DecisionEngine.evaluate_incident(
                risk_score,
                priority,
                len(
                    duplicate_candidates
                ),
            )
        )

        return DecisionResult(
            risk_score=risk_score,
            priority=priority,
            duplicate_candidates=
                duplicate_candidates,
            decisions=decisions,
            assignment=assignment,
            sla_snapshot=
                sla_snapshot,
        )
