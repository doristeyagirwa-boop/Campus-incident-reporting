from sqlalchemy.orm import Session

class UnitOfWork:

    def __init__(
        self,
        session: Session,
        *,
        owns_session: bool = False,
    ):

        self.session = session
        self.owns_session = owns_session
        self.committed = False
        self.rolled_back = False

    def __enter__(
        self,
    ):

        return self

    def commit(
        self,
    ):

        self.session.commit()
        self.committed = True

    def rollback(
        self,
    ):

        self.session.rollback()
        self.rolled_back = True

    def __exit__(
        self,
        exc_type,
        exc,
        tb,
    ):

        try:

            if exc_type is not None:
                self.rollback()

            elif not self.committed:
                self.commit()

        finally:

            if self.owns_session:
                self.session.close()

        return False
