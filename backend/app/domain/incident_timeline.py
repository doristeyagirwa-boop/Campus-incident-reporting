from dataclasses import dataclass, field

@dataclass
class TimelineEvent:
    event_type: str
    old_value: str | None
    new_value: str | None
    actor: str
    timestamp: str


@dataclass
class IncidentTimeline:
    incident_id: str
    events: list[TimelineEvent] = field(default_factory=list)
