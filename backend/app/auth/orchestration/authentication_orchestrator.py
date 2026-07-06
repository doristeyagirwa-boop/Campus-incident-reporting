from datetime import datetime

from app.auth.models.user import User

class AuthenticationOrchestrator:

    def __init__(
        self,
        authentication_service,
    ):

        self.authentication_service = (
            authentication_service
        )

    def register(
        self,
        institution_id,
        email,
        password,
        full_name,
        role,
    ):

        return (
            self.authentication_service
            .register(
                institution_id,
                email,
                password,
                full_name,
                role,
            )
        )

    def login(
        self,
        institution_id,
        email,
        password,
        ip_address,
        user_agent,
    ):

        return (
            self.authentication_service
            .login(
                institution_id,
                email,
                password,
                ip_address,
                user_agent,
            )
        )

    def refresh(
        self,
        refresh_token,
    ):
        return (
            self.authentication_service
            .refresh(
                refresh_token,
            )
        )

    def logout(
        self,
        refresh_token,
        session_id=None,
    ):
        return (
            self.authentication_service
            .logout(
                refresh_token,
                session_id,
            )
        )

    def me(
        self,
        access_token,
    ):
        return (
            self.authentication_service
            .me(
                access_token,
            )
        )
