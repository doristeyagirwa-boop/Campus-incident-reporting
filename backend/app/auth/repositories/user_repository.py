from sqlalchemy.orm import Session

from app.auth.models.user import User

class UserRepository:

    def __init__(
        self,
        db: Session,
    ):
        self.db = db

    def create(
        self,
        user: User,
    ):
        self.db.add(user)
        self.db.flush()
        self.db.refresh(user)
        return user

    def get_by_id(
        self,
        user_id,
    ):
        return (
            self.db.query(User)
            .filter(User.id == user_id)
            .first()
        )

    def get_by_email(
        self,
        email,
    ):
        return (
            self.db.query(User)
            .filter(User.email == email)
            .first()
        )

    def get_by_institution_id(
        self,
        institution_id,
    ):
        return (
            self.db.query(User)
            .filter(
                User.institution_id ==
                institution_id
            )
            .first()
        )

    def find_by_email(
        self,
        email,
    ):

        return (
            self.get_by_email(
                email
            )
        )

    def find_by_institution_id(
        self,
        institution_id,
    ):

        return (
            self.get_by_institution_id(
                institution_id
            )
        )

    def authenticate_identity(
        self,
        institution_id,
        email,
    ):

        return (
            self.db.query(
                User
            )

            .filter(
                User.institution_id == institution_id,
                User.email == email,
                User.is_active == True,
            )

            .first()
        )

    def update(
        self,
        user,
    ):
        self.db.flush()
        self.db.refresh(user)
        return user
