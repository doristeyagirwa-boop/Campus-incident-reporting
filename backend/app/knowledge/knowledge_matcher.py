class KnowledgeMatcher:

    @staticmethod
    def find_matches(
        text,
        articles,
    ):

        text = text.lower()

        matches = []

        for article in articles:

            title = (
                article.title.lower()
            )

            if title in text:

                matches.append(
                    article
                )

        return matches
