from collections import defaultdict


class InstitutionalMemoryEngine:

    @staticmethod
    def build_memory(
        incidents,
    ):

        memory = {

            "categories":
                defaultdict(int),

            "locations":
                defaultdict(int),

            "priorities":
                defaultdict(int),

            "risk_distribution":
                [],

            "incident_count":
                len(incidents),
        }

        for incident in incidents:

            memory["categories"][
                incident.category
            ] += 1

            memory["locations"][
                incident.location
            ] += 1

            memory["priorities"][
                incident.priority
            ] += 1

            memory[
                "risk_distribution"
            ].append(
                incident.risk_score
            )

        return memory
