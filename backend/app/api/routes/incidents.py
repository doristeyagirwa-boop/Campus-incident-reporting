from uuid import UUID

from fastapi import APIRouter
from fastapi import Depends

from app.api.dependencies import get_incident_application_service

from app.auth.dependencies import get_current_user
from app.auth.dependencies import require_roles

from app.schemas.assignment_schema import AssignmentSchema
from app.schemas.comment_schema import CommentCreateSchema
from app.schemas.comment_schema import CommentResponseSchema
from app.schemas.incident_filters import IncidentFilters
from app.schemas.incident_schema import IncidentCreateSchema
from app.schemas.status_update_schema import StatusUpdateSchema

from app.services.application_service import IncidentApplicationService


router = APIRouter(
    prefix="/incidents",
    tags=["Incidents"],
)


@router.post("/")
async def create_incident(
    payload: IncidentCreateSchema,
    current_user=Depends(
        get_current_user,
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):
    incident, workflow_context = (
        service.create_incident(
            payload
        )
    )

    return {
        "incident_id": incident.id,
        "incident_number": incident.incident_number,
        "priority": incident.priority,
        "risk_score": incident.risk_score,
        "created_by": current_user.get("institution_id"),
    }


@router.get("/")
async def list_incidents(
    status: str | None = None,
    priority: str | None = None,
    category: str | None = None,
    page: int = 1,
    page_size: int = 20,
    current_user=Depends(
        get_current_user,
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):
    filters = IncidentFilters(
        status=status,
        priority=priority,
        category=category,
        page=page,
        page_size=page_size,
    )

    return service.list_incidents(
        filters
    )


@router.get("/metrics/dashboard")
async def dashboard_metrics(
    current_user=Depends(
        require_roles(
            "ADMIN",
            "SUPER_ADMIN",
            "DEAN",
            "INVESTIGATOR",
            "SECURITY",
        ),
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):
    return service.get_metrics()


@router.get("/{incident_id}")
async def get_incident(
    incident_id: UUID,
    current_user=Depends(
        get_current_user,
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):
    return service.get_incident(
        incident_id
    )


@router.patch("/{incident_id}/status")
async def update_status(
    incident_id: UUID,
    payload: StatusUpdateSchema,
    current_user=Depends(
        require_roles(
            "ADMIN",
            "SUPER_ADMIN",
            "DEAN",
            "INVESTIGATOR",
            "TECHNICIAN",
            "SECURITY",
        ),
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):
    incident = service.update_status(
        incident_id=incident_id,
        target_status=payload.target_status,
        actor=current_user.get("institution_id"),
    )

    return {
        "incident_id": incident.id,
        "status": incident.status,
        "updated_by": current_user.get("institution_id"),
    }


@router.get("/{incident_id}/history")
async def get_history(
    incident_id: UUID,
    current_user=Depends(
        require_roles(
            "ADMIN",
            "SUPER_ADMIN",
            "DEAN",
            "INVESTIGATOR",
            "TECHNICIAN",
            "SECURITY",
        ),
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):
    history = service.get_history(
        incident_id
    )

    return [
        {
            "event_type": h.event_type,
            "old_value": h.old_value,
            "new_value": h.new_value,
            "performed_by": h.performed_by,
            "created_at": h.created_at,
        }
        for h in history
    ]


@router.patch("/{incident_id}/assign")
async def assign_technician(
    incident_id: UUID,
    payload: AssignmentSchema,
    current_user=Depends(
        require_roles(
            "ADMIN",
            "SUPER_ADMIN",
            "DEAN",
            "INVESTIGATOR",
            "SECURITY",
        ),
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):
    incident = service.assign_technician(
        incident_id,
        technician_id=payload.technician_id,
        actor=current_user.get("institution_id"),
        reason=payload.reason,
    )

    return {
        "incident_id": incident.id,
        "technician": incident.assigned_technician,
        "assigned_by": current_user.get("institution_id"),
    }


@router.get("/{incident_id}/timeline")
async def get_timeline(
    incident_id: UUID,
    current_user=Depends(
        get_current_user,
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):
    return service.get_timeline(
        incident_id
    )


@router.post(
    "/{incident_id}/comments",
    response_model=CommentResponseSchema,
    status_code=201,
)
async def add_comment(
    incident_id: UUID,
    payload: CommentCreateSchema,
    current_user=Depends(
        get_current_user,
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):

    payload.author = current_user.get(
        "institution_id"
    )

    return service.add_comment(
        incident_id,
        payload,
    )


@router.get("/{incident_id}/comments")
async def get_comments(
    incident_id: UUID,
    current_user=Depends(
        get_current_user,
    ),
    service: IncidentApplicationService = Depends(
        get_incident_application_service,
    ),
):
    return service.get_comments(
        incident_id
    )
