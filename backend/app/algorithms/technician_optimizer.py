class TechnicianOptimizer:

    @staticmethod
    def choose_best(
        technicians,
        incident,
    ):

        if not technicians:
            return None

        ranked = sorted(
            technicians,
            key=lambda tech: (

                tech.skill_score,

                tech.certification_score,

                tech.availability_score,

                -tech.workload_score,

            ),
            reverse=True,
        )

        return ranked[0]
