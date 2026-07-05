class RelationshipEngine:

    @staticmethod
    def build_links(
        incident,
        graph,
    ):

        links = []

        if (
            incident.location
            in graph.locations
        ):
            links.append(
                (
                    "LOCATION",
                    incident.location,
                )
            )

        if (
            incident.category
            in graph.departments
        ):
            links.append(
                (
                    "DEPARTMENT",
                    incident.category,
                )
            )

        return links
