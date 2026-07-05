class TransactionManager:

    def __init__(
        self,
        db,
    ):
        self.db = db

    def __enter__(
        self,
    ):
        return self.db

    def __exit__(
        self,
        exc_type,
        exc,
        tb,
    ):

        if exc:

            self.db.rollback()

        else:

            self.db.commit()
