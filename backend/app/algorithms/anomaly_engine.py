import statistics


class AnomalyEngine:

    @staticmethod
    def evaluate(
        risk_score,
        incidents,
    ):

        if not incidents:

            return False

        scores = [

            i.risk_score
            for i in incidents
        ]

        mean = statistics.mean(
            scores
        )

        stdev = (
            statistics.stdev(scores)
            if len(scores) > 1
            else 0
        )

        threshold = (
            mean
            + (2 * stdev)
        )

        return (
            risk_score > threshold
        )
