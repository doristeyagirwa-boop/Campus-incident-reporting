class RootCauseRepository:

    def __init__(self):

        self.causes = {}

    def register(
        self,
        category,
        root_cause,
    ):

        self.causes.setdefault(
            category,
            [],
        )

        self.causes[
            category
        ].append(
            root_cause
        )

    def get_causes(
        self,
        category,
    ):

        return self.causes.get(
            category,
            [],
        )
