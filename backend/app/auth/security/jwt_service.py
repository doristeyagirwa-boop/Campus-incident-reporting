from datetime import datetime
from datetime import timedelta

from jose import jwt

from app.core.config import settings

class JWTService:

    def create_access_token(
        self,
        payload,
    ):

        data = payload.copy()

        expire = (
            datetime.utcnow()
            +
            timedelta(
                minutes=settings.ACCESS_TOKEN_EXPIRE_MINUTES
            )
        )

        data["exp"] = expire

        return jwt.encode(
            data,
            settings.JWT_SECRET_KEY,
            algorithm=settings.JWT_ALGORITHM,
        )

    def create_refresh_token(
        self,
        payload,
    ):

        data = payload.copy()

        expire = (
            datetime.utcnow()
            +
            timedelta(days=30)
        )

        data["exp"] = expire

        return jwt.encode(
            data,
            settings.JWT_SECRET_KEY,
            algorithm=settings.JWT_ALGORITHM,
        )

    def decode(
        self,
        token,
    ):

        return jwt.decode(
            token,
            settings.JWT_SECRET_KEY,
            algorithms=[
                settings.JWT_ALGORITHM
            ],
        )


jwt_service = JWTService()
