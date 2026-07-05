class SearchEngine:

    @staticmethod
    def normalize(
        text: str,
    ):

        return (
            text
            .strip()
            .lower()
        )

    @classmethod
    def keyword_match(
        cls,
        query,
        title,
        description,
    ):

        query = cls.normalize(
            query
        )

        content = (
            f"{title} "
            f"{description}"
        ).lower()

        return query in content
