class TechnicianRegistry:

    def __init__(self):

        self.technicians = {}

    def register(
        self,
        technician,
    ):

        self.technicians[
            technician.technician_id
        ] = technician

    def get(
        self,
        technician_id,
    ):

        return self.technicians.get(
            technician_id
        )

    def all(
        self,
    ):

        return list(
            self.technicians.values()
        )
