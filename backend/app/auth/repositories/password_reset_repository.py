from app.auth.models.password_reset_token import PasswordResetToken

class PasswordResetRepository:

    def __init__(
        self,
        db,
    ):
        self.db = db

    def create(
        self,
        token,
    ):
        self.db.add(token)
        self.db.flush()
        self.db.refresh(token)
        return token

    def get(
        self,
        token_hash,
    ):
        return (
            self.db.query(
                PasswordResetToken
            )
            .filter(
                PasswordResetToken.token_hash
                ==
                token_hash
            )
            .first()
        )
