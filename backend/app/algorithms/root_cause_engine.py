from collections import Counter


class RootCauseEngine:

    @staticmethod
    def infer_root_causes(
        incidents,
    ):

        causes = []

        for incident in incidents:

            if hasattr(
                incident,
                "root_cause",
            ):

                if incident.root_cause:

                    causes.append(
                        incident.root_cause
                    )

        return Counter(
            causes
        ).most_common(10)
