from dataclasses import dataclass


@dataclass
class LifecycleState:

    current_state: str

    allowed_transitions: list


LIFECYCLE = {

    "SUBMITTED":
        LifecycleState(
            current_state="SUBMITTED",
            allowed_transitions=[
                "ASSIGNED",
                "CLOSED",
            ],
        ),

    "ASSIGNED":
        LifecycleState(
            current_state="ASSIGNED",
            allowed_transitions=[
                "INVESTIGATING",
                "ESCALATED",
            ],
        ),

    "INVESTIGATING":
        LifecycleState(
            current_state="INVESTIGATING",
            allowed_transitions=[
                "RESOLVED",
                "ESCALATED",
            ],
        ),

     "ESCALATED":
        LifecycleState(
            current_state="ESCALATED",
            allowed_transitions=[
                "INVESTIGATING",
                "RESOLVED",
            ],
        ),

    "RESOLVED":
        LifecycleState(
            current_state="RESOLVED",
            allowed_transitions=[
                "CLOSED",
                "REOPENED",
            ],
        ),

    "REOPENED":
        LifecycleState(
            current_state="REOPENED",
            allowed_transitions=[
                "ASSIGNED",
                "INVESTIGATING",
            ],
        ),

    "CLOSED":
        LifecycleState(
            current_state="CLOSED",
            allowed_transitions=[],
        ),
}

def can_transition(
    current_state: str,
    target_state: str,
) -> bool:
    state = LIFECYCLE.get(current_state)

    if state is None:
        return False

    return target_state in state.allowed_transitions
