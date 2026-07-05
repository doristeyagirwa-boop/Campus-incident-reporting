from collections import defaultdict


class DependencyGraphEngine:

    def __init__(self):

        self.graph = (
            defaultdict(list)
        )

    def add_dependency(
        self,
        source,
        target,
    ):

        self.graph[
            source
        ].append(
            target
        )

    def get_dependencies(
        self,
        node,
    ):

        return self.graph.get(
            node,
            [],
        )
