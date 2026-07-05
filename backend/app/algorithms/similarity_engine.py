from sklearn.feature_extraction.text import (
    TfidfVectorizer,
)

from sklearn.metrics.pairwise import (
    cosine_similarity,
)


class SimilarityEngine:

    @staticmethod
    def compare(
        text_one: str,
        text_two: str,
    ) -> float:

        vectorizer = TfidfVectorizer()

        matrix = vectorizer.fit_transform(
            [
                text_one,
                text_two,
            ]
        )

        similarity = cosine_similarity(
            matrix[0],
            matrix[1],
        )[0][0]

        return round(
            similarity * 100,
            2,
        )

        @staticmethod
        def find_duplicates(
            new_text,
            existing_incidents,
            threshold=70,
        ):

            results = []

            for incident in existing_incidents:

                score = (
                    SimilarityEngine.compare(
                        new_text,
                        incident.description,
                    )
                )

                if score >= threshold:

                    results.append(
                        {
                            "incident_id":
                                incident.id,
                            "incident_number":
                                incident.incident_number,
                            "score":
                                score,
                        }
                    )

            results.sort(
                key=lambda item:
                item["score"],
                reverse=True,
            )

            return results
