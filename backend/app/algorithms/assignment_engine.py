from app.algorithms.technician_optimizer import (
    TechnicianOptimizer,
)


class AssignmentEngine:

    @staticmethod
    def assign(
        technicians,
        incident,
    ):

        return (
            TechnicianOptimizer
            .choose_best(
                technicians,
                incident,
            )
        )
