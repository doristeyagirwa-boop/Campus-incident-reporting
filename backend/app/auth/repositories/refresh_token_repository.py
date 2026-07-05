from app.auth.models.refresh_token import RefreshToken

class RefreshTokenRepository:

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
                RefreshToken
            )
            .filter(
                RefreshToken.token_hash
                ==
                token_hash
            )
            .first()
        )

    def revoke(
        self,
        token,
    ):
        token.revoked = True
        self.db.flush()
