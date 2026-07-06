from app.algorithms.assignment_engine import (
    AssignmentEngine,
)


class AssignmentStage:

    @staticmethod
    def execute(
        technicians,
        incident,
    ):

        return (
            AssignmentEngine.assign(
                technicians,
                incident,
            )
        )
