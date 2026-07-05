from app.auth.models.role import Role

class RoleRepository:

    def __init__(
        self,
        db,
    ):
        self.db = db

    def create(
        self,
        role: Role,
    ):
        self.db.add(role)
        self.db.flush()
        self.db.refresh(role)
        return role

    def get_by_id(
        self,
        role_id,
    ):
        return (
            self.db.query(Role)
            .filter(Role.id == role_id)
            .first()
        )

    def get_by_name(
        self,
        name: str,
    ):
        return (
            self.db.query(Role)
            .filter(Role.name == name.upper())
            .first()
        )

    def list_all(
        self,
    ):
        return (
            self.db.query(Role)
            .order_by(Role.name.asc())
            .all()
        )
