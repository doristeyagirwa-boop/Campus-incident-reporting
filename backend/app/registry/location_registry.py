class LocationRegistry:

    def __init__(self):

        self.locations = {}

    def register(
        self,
        location,
    ):

        self.locations[
            location.location_id
        ] = location

    def get(
        self,
        location_id,
    ):

        return self.locations.get(
            location_id
        )

    def all(
        self,
    ):

        return list(
            self.locations.values()
        )
