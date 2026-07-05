class RiskProfileRegistry:

    def __init__(self):

        self.profiles = {}

    def register(
        self,
        profile,
    ):

        self.profiles[
            profile.profile_id
        ] = profile

    def get(
        self,
        profile_id,
    ):

        return self.profiles.get(
            profile_id
        )

    def all(
        self,
    ):

        return list(
            self.profiles.values()
        )
