from app.core.constants import IncidentStatus


ALLOWED_TRANSITIONS = {
    IncidentStatus.DRAFT: [
        IncidentStatus.SUBMITTED
    ],

    IncidentStatus.SUBMITTED: [
        IncidentStatus.VALIDATED,
        IncidentStatus.ARCHIVED,
    ],

    IncidentStatus.VALIDATED: [
        IncidentStatus.QUEUED,
    ],

    IncidentStatus.QUEUED: [
        IncidentStatus.ASSIGNED,
    ],

    IncidentStatus.ASSIGNED: [
        IncidentStatus.INVESTIGATING,
    ],

    IncidentStatus.INVESTIGATING: [
        IncidentStatus.AWAITING_USER,
        IncidentStatus.RESOLVED,
        IncidentStatus.ESCALATED,
    ],

    IncidentStatus.AWAITING_USER: [
        IncidentStatus.INVESTIGATING,
        IncidentStatus.RESOLVED,
    ],

    IncidentStatus.ESCALATED: [
        IncidentStatus.INVESTIGATING,
        IncidentStatus.RESOLVED,
    ],

    IncidentStatus.RESOLVED: [
        IncidentStatus.VERIFIED,
        IncidentStatus.REOPENED,
    ],

    IncidentStatus.VERIFIED: [
        IncidentStatus.CLOSED,
    ],

    IncidentStatus.REOPENED: [
        IncidentStatus.INVESTIGATING,
    ],

    IncidentStatus.CLOSED: [],
    IncidentStatus.ARCHIVED: [],
}


def can_transition(
    current_status,
    target_status,
):
    return (
        target_status
        in ALLOWED_TRANSITIONS.get(
            current_status,
            [],
        )
    )
