from app.auth.models.login_attempt import LoginAttempt

class LoginAttemptRepository:

    def __init__(
        self,
        db,
    ):
        self.db = db

    def create(
        self,
        attempt,
    ):
        self.db.add(attempt)
        self.db.flush()
        self.db.refresh(attempt)
        return attempt
