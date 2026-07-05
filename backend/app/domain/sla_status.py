from dataclasses import dataclass
from datetime import datetime


@dataclass
class SLAStatus:

    response_due: datetime

    resolution_due: datetime

    response_breached: bool

    resolution_breached: bool
