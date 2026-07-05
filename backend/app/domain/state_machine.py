from app.workflows.lifecycle import (
    LIFECYCLE,
)


class IncidentStateMachine:

    @staticmethod
    def validate_transition(
        current_status: str,
        target_status: str,
    ):

        state = (
            LIFECYCLE.get(
                current_status
            )
        )

        if state is None:

            return False

        return (
            target_status
            in
            state.allowed_transitions
        )
