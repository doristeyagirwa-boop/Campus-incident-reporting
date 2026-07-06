from fastapi import Depends
from fastapi import Header
from fastapi import HTTPException

from starlette.status import HTTP_401_UNAUTHORIZED
from starlette.status import HTTP_403_FORBIDDEN

from app.api.dependencies import (
    get_authentication_application_service,
)


def get_current_user(
    authorization: str | None = Header(default=None),
    service=Depends(
        get_authentication_application_service,
    ),
):
    if not authorization:
        raise HTTPException(
            status_code=HTTP_401_UNAUTHORIZED,
            detail="Missing Authorization header.",
        )

    if not authorization.startswith("Bearer "):
        raise HTTPException(
            status_code=HTTP_401_UNAUTHORIZED,
            detail="Invalid Authorization header.",
        )

    access_token = authorization.replace(
        "Bearer ",
        "",
        1,
    )

    try:
        return service.me(
            access_token
        )

    except ValueError as exc:
        raise HTTPException(
            status_code=HTTP_401_UNAUTHORIZED,
            detail=str(exc),
        ) from exc


def require_roles(
    *allowed_roles,
):
    def dependency(
        current_user=Depends(
            get_current_user,
        ),
    ):
        role = current_user.get(
            "role"
        )

        if role not in allowed_roles:
            raise HTTPException(
                status_code=HTTP_403_FORBIDDEN,
                detail="Insufficient permissions.",
            )

        return current_user

    return dependency
