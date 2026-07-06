from app.auth.models.login_session import LoginSession


class LoginSessionRepository:

    def __init__(
        self,
        db,
    ):
        self.db = db

    def create(
        self,
        session,
    ):
        self.db.add(session)
        self.db.flush()
        self.db.refresh(session)
        return session

    def get_by_id(
        self,
        session_id,
    ):
        return (
            self.db.query(LoginSession)
            .filter(LoginSession.id == session_id)
            .first()
        )

    def revoke(
        self,
        session,
    ):
        session.revoked = True
        self.db.flush()
        return session

    def active_sessions(
        self,
        user_id,
    ):
        return (
            self.db.query(LoginSession)
            .filter(
                LoginSession.user_id == user_id,
                LoginSession.revoked == False,
            )
            .all()
        )
