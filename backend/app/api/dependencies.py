from typing import Any

from fastapi import Depends
from sqlalchemy.orm import Session

from app.db.session import get_db
from app.services.service_factory import ServiceFactory
from app.services.application_service import IncidentApplicationService
from app.auth.application_service import AuthenticationApplicationService

def get_application_services(
    db: Session = Depends(get_db),
) -> dict[str, Any]:
    context, container, authentication_service = (
        ServiceFactory.build_context(db)
    )

    return {
        "incident": IncidentApplicationService(
            context=context,
            container=container,
        ),
        "authentication": AuthenticationApplicationService(
            authentication_service,
            context.unit_of_work,
        ),
    }


def get_incident_application_service(
    services: dict[str, Any] = Depends(get_application_services),
):
    return services["incident"]


def get_authentication_application_service(
    services: dict[str, Any] = Depends(get_application_services),
):
    return services["authentication"]
