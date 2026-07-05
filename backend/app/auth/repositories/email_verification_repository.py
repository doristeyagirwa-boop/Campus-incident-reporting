from app.auth.models.email_verification_token import EmailVerificationToken


class EmailVerificationRepository:

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
                EmailVerificationToken
            )
            .filter(
                EmailVerificationToken.token_hash
                ==
                token_hash
            )
            .first()
        )
