from app.auth.orchestration.authentication_orchestrator import (
    AuthenticationOrchestrator,
)


class AuthenticationApplicationService:

    def __init__(
        self,
        authentication_service,
        unit_of_work,
    ):
        self.unit_of_work = unit_of_work

        self.authentication_orchestrator = (
            AuthenticationOrchestrator(
                authentication_service
            )
        )

    def register(
        self,
        payload,
    ):
        with self.unit_of_work:
            return (
                self.authentication_orchestrator
                .register(
                    payload.institution_id,
                    payload.email,
                    payload.password,
                    payload.full_name,
                    payload.role,
                )
            )

    def login(
        self,
        payload,
        ip_address,
        user_agent,
    ):
        with self.unit_of_work:
            return (
                self.authentication_orchestrator
                .login(
                    payload.institution_id,
                    payload.email,
                    payload.password,
                    ip_address,
                    user_agent,
                )
            )

    def refresh(
        self,
        payload,
    ):
        with self.unit_of_work:
            return (
                self.authentication_orchestrator
                .refresh(
                    payload.refresh_token,
                )
            )

    def logout(
        self,
        payload,
    ):
        with self.unit_of_work:
            return (
                self.authentication_orchestrator
                .logout(
                    payload.refresh_token,
                    payload.session_id,
                )
            )

    def me(
        self,
        access_token,
    ):
        with self.unit_of_work:
            return (
                self.authentication_orchestrator
                .me(
                    access_token,
                )
            )
