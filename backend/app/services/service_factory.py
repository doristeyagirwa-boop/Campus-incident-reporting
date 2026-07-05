from sqlalchemy.orm import Session

from app.repositories.incident_repository import IncidentRepository
from app.repositories.history_repository import HistoryRepository
from app.repositories.comment_repository import CommentRepository
from app.repositories.attachment_repository import AttachmentRepository

from app.services.audit_service import AuditService
from app.services.service_context import ServiceContext
from app.services.unit_of_work import UnitOfWork

from app.domain.event_publisher import EventPublisher

from app.domain.listeners import (
    HistoryListener,
    AuditListener,
    NotificationListener,
    ProjectionListener,
)

from app.domain.event_subscriber_registry import EventSubscriberRegistry
from app.repositories.audit_repository import AuditRepository

from app.services.service_container import ServiceContainer

from app.registry.policy_registry import PolicyRegistry
from app.registry.sla_registry import SLA_REGISTRY

from app.domain.projections.incident_projection import IncidentProjection
from app.domain.projections.assignment_projection import AssignmentProjection
from app.domain.listeners import ProjectionListener

from app.auth.repositories.user_repository import UserRepository

from app.auth.repositories.refresh_token_repository import RefreshTokenRepository
from app.auth.repositories.login_attempt_repository import LoginAttemptRepository
from app.auth.repositories.login_session_repository import LoginSessionRepository
from app.auth.repositories.password_reset_repository import PasswordResetRepository
from app.auth.repositories.email_verification_repository import EmailVerificationRepository
from app.auth.services.authentication_service import AuthenticationService
from app.auth.repositories.role_repository import RoleRepository

class ServiceFactory:

    @staticmethod
    def build_context(
        db: Session,
    ):

        # Repositories

        incident_repository = IncidentRepository(db)

        history_repository = HistoryRepository(db)

        comment_repository = CommentRepository(db)

        attachment_repository = AttachmentRepository(db)

        audit_repository = AuditRepository(db)

        user_repository = UserRepository(db)

        role_repository = RoleRepository(db)

        refresh_token_repository = RefreshTokenRepository(db)

        login_attempt_repository = LoginAttemptRepository(db)

        login_session_repository = (
            LoginSessionRepository(db)
        )

        password_reset_repository = (
            PasswordResetRepository(db)
        )

        email_verification_repository = (
            EmailVerificationRepository(db)
        )

        # Service Container

        container = (
            ServiceContainer()
        )

        # Infrastructure

        unit_of_work = (
            UnitOfWork(db)
        )

        audit_service = (
            AuditService(
                audit_repository
            )
        )

        authentication_service = (
            AuthenticationService(
                user_repository=
                    user_repository,

                role_repository=
                    role_repository,

                refresh_token_repository=
                    refresh_token_repository,

                login_attempt_repository=
                    login_attempt_repository,

                login_session_repository=
                    login_session_repository,

                password_reset_repository=
                    password_reset_repository,

                email_verification_repository=
                    email_verification_repository,

                password_hasher=
                    container.password_hasher,

                jwt_service=
                    container.jwt_service,

                token_generator=
                    container.token_generator,

                password_policy=
                    container.password_policy,
            )
        )

        # Event Bus

        event_publisher = (
            EventPublisher()
        )

        history_listener = (
            HistoryListener(
                history_repository
            )
        )

        audit_listener = (
            AuditListener(
                audit_service
            )
        )

        notification_listener = NotificationListener(
            container.notification_service
        )

        incident_projection=(
            IncidentProjection()
        )

        assignment_projection=(
            AssignmentProjection()
        )

        incident_projection_listener=(
            ProjectionListener(
                incident_projection,
            )
        )

        assignment_projection_listener=(
            ProjectionListener(
                assignment_projection,
            )
        )

        # Event Subscriptions

        EventSubscriberRegistry.register(
            event_publisher,
            history_listener,
            audit_listener,
            notification_listener,
            incident_projection_listener,
            assignment_projection_listener,
        )

        # Context

        return (
            ServiceContext(

            incident_repository=
                incident_repository,

            history_repository=
                history_repository,

            comment_repository=
                comment_repository,

            attachment_repository=
                attachment_repository,

            audit_repository=
                audit_repository,

            audit_service=
                audit_service,

            event_publisher=
                event_publisher,

            unit_of_work=
                unit_of_work,

            technician_registry=
                container.technician_registry,

            asset_registry=
                container.asset_registry,

            location_registry=
                container.location_registry,

            policy_registry=
                container.policy_registry,

            risk_profile_registry=
                container.risk_profile_registry,

            sla_registry=
                SLA_REGISTRY,
        ),
        container,
        authentication_service,
    )
