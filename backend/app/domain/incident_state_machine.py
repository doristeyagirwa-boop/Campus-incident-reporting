from enum import Enum


class IncidentStatus(str, Enum):

    SUBMITTED = "SUBMITTED"

    ASSIGNED = "ASSIGNED"

    INVESTIGATING = "INVESTIGATING"

    ESCALATED = "ESCALATED"

    RESOLVED = "RESOLVED"

    CLOSED = "CLOSED"


class IncidentStateMachine:

    VALID_TRANSITIONS = {

        IncidentStatus.SUBMITTED: {

            IncidentStatus.ASSIGNED,

            IncidentStatus.CLOSED,

        },

        IncidentStatus.ASSIGNED: {

            IncidentStatus.INVESTIGATING,

            IncidentStatus.SUBMITTED,

        },

        IncidentStatus.INVESTIGATING: {

            IncidentStatus.ESCALATED,

            IncidentStatus.RESOLVED,

            IncidentStatus.ASSIGNED,

        },

        IncidentStatus.ESCALATED: {

            IncidentStatus.INVESTIGATING,

            IncidentStatus.RESOLVED,

        },

        IncidentStatus.RESOLVED: {

            IncidentStatus.CLOSED,

            IncidentStatus.INVESTIGATING,

        },

        IncidentStatus.CLOSED: set(),
    }

    @classmethod
    def validate_transition(

        cls,

        current_status: str,

        target_status: str,

    ):

        current = IncidentStatus(current_status)

        target = IncidentStatus(target_status)

        return (

            target

            in

            cls.VALID_TRANSITIONS[current]

        )
