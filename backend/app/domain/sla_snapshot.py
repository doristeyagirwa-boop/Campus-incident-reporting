from dataclasses import dataclass
from datetime import datetime


@dataclass
class SLASnapshot:

    response_due: datetime

    resolution_due: datetime

    breached: bool = False
