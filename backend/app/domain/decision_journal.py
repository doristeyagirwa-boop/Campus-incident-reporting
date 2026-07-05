from dataclasses import dataclass
from dataclasses import field


@dataclass
class DecisionJournal:

    entries: list = field(
        default_factory=list
    )

    def record(
        self,
        stage,
        message,
    ):

        self.entries.append(
            {
                "stage": stage,
                "message": message,
            }
        )
