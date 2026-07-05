from fastapi import APIRouter
from fastapi import Depends
from fastapi import HTTPException
from fastapi import Request
from fastapi import Header

from starlette.status import HTTP_400_BAD_REQUEST
from starlette.status import HTTP_401_UNAUTHORIZED

from app.api.dependencies import (
    get_authentication_application_service,
)

from app.auth.schemas.login_schema import LoginSchema
from app.auth.schemas.register_schema import RegisterSchema
from app.auth.schemas.refresh_schema import RefreshTokenSchema
from app.auth.schemas.logout_schema import LogoutSchema

router = APIRouter(
    prefix="/auth",
    tags=["Authentication"],
)


@router.post("/register")
def register(
    payload: RegisterSchema,
    service=Depends(
        get_authentication_application_service,
    ),
):
    try:
        return service.register(payload)

    except ValueError as exc:
        raise HTTPException(
            status_code=HTTP_400_BAD_REQUEST,
            detail=str(exc),
        ) from exc


@router.post("/login")
def login(
    request: Request,
    payload: LoginSchema,
    service=Depends(
        get_authentication_application_service,
    ),
):
    try:
        return service.login(
            payload,
            request.client.host if request.client else None,
            request.headers.get("User-Agent", ""),
        )

    except ValueError as exc:
        raise HTTPException(
            status_code=HTTP_401_UNAUTHORIZED,
            detail=str(exc),
        ) from exc

@router.post("/refresh")
def refresh(
    payload: RefreshTokenSchema,
    service=Depends(
        get_authentication_application_service,
    ),
):
    try:
        return service.refresh(payload)

    except ValueError as exc:
        raise HTTPException(
            status_code=HTTP_401_UNAUTHORIZED,
            detail=str(exc),
        ) from exc

@router.post("/logout")
def logout(
    payload: LogoutSchema,
    service=Depends(
        get_authentication_application_service,
    ),
):
    try:
        return service.logout(payload)

    except ValueError as exc:
        raise HTTPException(
            status_code=HTTP_401_UNAUTHORIZED,
            detail=str(exc),
        ) from exc


@router.get("/me")
def me(
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
