from datetime import datetime
from datetime import timedelta
from datetime import timezone

from hashlib import sha256

from app.auth.models.login_attempt import LoginAttempt
from app.auth.models.login_session import LoginSession
from app.auth.models.refresh_token import RefreshToken
from app.auth.models.user import User

from jose.exceptions import JWTError

class AuthenticationService:

    def __init__(
        self,
        user_repository,
        role_repository,
        refresh_token_repository,
        login_session_repository,
        login_attempt_repository,
        password_reset_repository,
        email_verification_repository,
        password_hasher,
        jwt_service,
        token_generator,
        password_policy,
    ):
        self.user_repository = user_repository
        self.role_repository = role_repository
        self.refresh_token_repository = refresh_token_repository
        self.login_session_repository = login_session_repository
        self.login_attempt_repository = login_attempt_repository
        self.password_reset_repository = password_reset_repository
        self.email_verification_repository = email_verification_repository
        self.password_hasher = password_hasher
        self.jwt_service = jwt_service
        self.token_generator = token_generator
        self.password_policy = password_policy

    def _split_full_name(
        self,
        full_name: str,
    ) -> tuple[str, str]:
        parts = full_name.strip().split(maxsplit=1)

        if not parts:
            return "Unknown", "User"

        first_name = parts[0]
        last_name = parts[1] if len(parts) > 1 else "-"

        return first_name, last_name

    def _safe_user_payload(
        self,
        user,
        role_name=None,
    ):
        return {
            "id": str(user.id),
            "institution_id": user.institution_id,
            "email": user.email,
            "role_id": str(user.role_id),
            "role": role_name,
            "is_active": user.is_active,
            "email_verified": user.email_verified,
        }

    def _hash_token(
        self,
        token: str,
    ) -> str:
        return sha256(
            token.encode()
        ).hexdigest()

    def _record_login_attempt(
        self,
        institution_id,
        email,
        ip_address,
        success,
    ):
        attempt = LoginAttempt(
            institution_id=institution_id,
            email=email,
            ip_address=ip_address,
            success=success,
        )

        created_attempt = self.login_attempt_repository.create(
            attempt
        )

        self.login_attempt_repository.db.commit()

        return created_attempt

    def _create_login_session(
        self,
        user,
        ip_address,
        user_agent,
    ):
        session = LoginSession(
            user_id=user.id,
            device_name=None,
            ip_address=ip_address,
            user_agent=user_agent,
            expires_at=(
                datetime.utcnow()
                +
                timedelta(days=30)
            ),
            revoked=False,
        )

        return self.login_session_repository.create(
            session
        )

    def _store_refresh_token(
        self,
        user,
        refresh_token,
    ):
        token_record = RefreshToken(
            user_id=user.id,
            token_hash=self._hash_token(
                refresh_token
            ),
            expires_at=(
                datetime.utcnow()
                +
                timedelta(days=30)
            ),
            revoked=False,
        )

        return self.refresh_token_repository.create(
            token_record
        )

    def register(
        self,
        institution_id,
        email,
        password,
        full_name,
        role,
    ):
        self.password_policy.validate(password)

        if self.user_repository.find_by_email(email):
            raise ValueError("Email already exists.")

        if self.user_repository.find_by_institution_id(
            institution_id,
        ):
            raise ValueError("Institution ID already exists.")

        role_record = self.role_repository.get_by_name(role)

        if role_record is None:
            raise ValueError(
                f"Role not found: {role}"
            )

        hashed = self.password_hasher.hash(password)

        first_name, last_name = self._split_full_name(
            full_name
        )

        user = User(
            institution_id=institution_id,
            email=email,
            password_hash=hashed,
            first_name=first_name,
            last_name=last_name,
            role_id=role_record.id,
            is_active=True,
            email_verified=False,
        )

        created_user = self.user_repository.create(user)

        return self._safe_user_payload(
            created_user,
            role_record.name,
        )

    def login(
        self,
        institution_id,
        email,
        password,
        ip_address,
        user_agent,
    ):
        user = self.user_repository.authenticate_identity(
            institution_id,
            email,
        )

        if user is None:
            self._record_login_attempt(
                institution_id,
                email,
                ip_address,
                False,
            )

            raise ValueError("Invalid credentials.")

        if not user.is_active:
            self._record_login_attempt(
                institution_id,
                email,
                ip_address,
                False,
            )

            raise ValueError("Account is inactive.")

        if getattr(user, "account_locked", False):
            self._record_login_attempt(
                institution_id,
                email,
                ip_address,
                False,
            )

            raise ValueError("Account is locked.")

        if not self.password_hasher.verify(
            user.password_hash,
            password,
        ):
            self._record_login_attempt(
                institution_id,
                email,
                ip_address,
                False,
            )

            raise ValueError("Invalid credentials.")

        role_record = self.role_repository.get_by_id(
            user.role_id
        )

        role_name = (
            role_record.name
            if role_record is not None
            else None
        )

        access_token = self.jwt_service.create_access_token(
            {
                "sub": str(user.id),
                "institution_id": user.institution_id,
                "email": user.email,
                "role_id": str(user.role_id),
                "role": role_name,
                "type": "access",
            }
        )

        refresh_token = self.jwt_service.create_refresh_token(
            {
                "sub": str(user.id),
                "institution_id": user.institution_id,
                "type": "refresh",
            }
        )

        session = self._create_login_session(
            user,
            ip_address,
            user_agent,
        )

        self._store_refresh_token(
            user,
            refresh_token,
        )

        self._record_login_attempt(
            institution_id,
            email,
            ip_address,
            True,
        )

        return {
            "user": self._safe_user_payload(
                user,
                role_name,
            ),
            "session_id": str(session.id),
            "access_token": access_token,
            "refresh_token": refresh_token,
            "token_type": "bearer",
        }

    def _now(
        self,
    ):
        return datetime.now(
            timezone.utc
        )

    def refresh(
        self,
        refresh_token,
    ):
        try:
            payload = self.jwt_service.decode(
                refresh_token
            )

        except JWTError as exc:
            raise ValueError(
                "Invalid refresh token."
            ) from exc

        if payload.get("type") != "refresh":
            raise ValueError("Invalid refresh token.")

        user_id = payload.get("sub")

        if not user_id:
            raise ValueError("Invalid refresh token.")

        token_hash = self._hash_token(
            refresh_token
        )

        token_record = self.refresh_token_repository.get(
            token_hash
        )

        if token_record is None:
            raise ValueError("Refresh token not recognized.")

        if token_record.revoked:
            raise ValueError("Refresh token has been revoked.")

        if token_record.expires_at <= self._now():
            raise ValueError("Refresh token has expired.")

        user = self.user_repository.get_by_id(
            user_id
        )

        if user is None:
            raise ValueError("User not found.")

        if not user.is_active:
            raise ValueError("Account is inactive.")

        if getattr(user, "account_locked", False):
            raise ValueError("Account is locked.")

        role_record = self.role_repository.get_by_id(
            user.role_id
        )

        role_name = (
            role_record.name
            if role_record is not None
            else None
        )

        self.refresh_token_repository.revoke(
            token_record
        )

        new_access_token = self.jwt_service.create_access_token(
            {
                "sub": str(user.id),
                "institution_id": user.institution_id,
                "email": user.email,
                "role_id": str(user.role_id),
                "role": role_name,
                "type": "access",
            }
        )

        new_refresh_token = self.jwt_service.create_refresh_token(
            {
                "sub": str(user.id),
                "institution_id": user.institution_id,
                "type": "refresh",
            }
        )

        self._store_refresh_token(
            user,
            new_refresh_token,
        )

        return {
            "user": self._safe_user_payload(
                user,
                role_name,
            ),
            "access_token": new_access_token,
            "refresh_token": new_refresh_token,
            "token_type": "bearer",
        }

    def logout(
        self,
        refresh_token,
        session_id=None,
    ):
        try:
            payload = self.jwt_service.decode(
                refresh_token
            )

        except JWTError as exc:
            raise ValueError(
                "Invalid refresh token."
            ) from exc

        if payload.get("type") != "refresh":
            raise ValueError("Invalid refresh token.")

        token_hash = self._hash_token(
            refresh_token
        )

        token_record = self.refresh_token_repository.get(
            token_hash
        )

        if token_record is None:
            raise ValueError("Refresh token not recognized.")

        if not token_record.revoked:
            self.refresh_token_repository.revoke(
                token_record
            )

        if session_id:
            session = self.login_session_repository.get_by_id(
                session_id
            )

            if session is not None and not session.revoked:
                self.login_session_repository.revoke(
                    session
                )

        return {
            "logged_out": True,
        }

    def me(
        self,
        access_token,
    ):
        try:
            payload = self.jwt_service.decode(
                access_token
            )

        except JWTError as exc:
            raise ValueError(
                "Invalid access token."
            ) from exc

        if payload.get("type") != "access":
            raise ValueError("Invalid access token.")

        user_id = payload.get("sub")

        if not user_id:
            raise ValueError("Invalid access token.")

        user = self.user_repository.get_by_id(
            user_id
        )

        if user is None:
            raise ValueError("User not found.")

        if not user.is_active:
            raise ValueError("Account is inactive.")

        if getattr(user, "account_locked", False):
            raise ValueError("Account is locked.")

        role_record = self.role_repository.get_by_id(
            user.role_id
        )

        role_name = (
            role_record.name
            if role_record is not None
            else None
        )

        return self._safe_user_payload(
            user,
            role_name,
        )
