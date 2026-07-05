from fastapi import APIRouter

from app.api.routes.incidents import (
    router as incident_router,
)

from app.api.routes.system import (
    router as system_router,
)

from app.auth.api.routes import (
    router as auth_router,
)

api_router = APIRouter()

api_router.include_router(
    system_router,
)

api_router.include_router(
    auth_router,
)

api_router.include_router(
    incident_router,
)
