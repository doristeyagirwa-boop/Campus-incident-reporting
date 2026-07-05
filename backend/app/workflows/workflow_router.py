class WorkflowRouter:

    @staticmethod
    def route(
        priority,
    ):

        if priority == "CRITICAL":
            return "CRITICAL_FLOW"

        if priority == "HIGH":
            return "HIGH_FLOW"

        return "STANDARD_FLOW"
