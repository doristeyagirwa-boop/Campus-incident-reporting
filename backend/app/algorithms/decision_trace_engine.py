class DecisionTraceEngine:

    @staticmethod
    def trace(
        context,
    ):

        return {

            "classification":
                context.classification,

            "risk_score":
                context.risk_score,

            "priority":
                context.priority,

            "duplicate":
                context.duplicate_found,

            "business_impact":
                context.business_impact_score,

            "approval_required":
                context.approval_required,
        }
