from collections import defaultdict
from collections.abc import Callable

from app.domain.events import DomainEvent


class EventBus:

    def __init__(self):

        self.handlers: dict[
            type[DomainEvent],
            list[Callable],
        ] = defaultdict(list)

    def subscribe(
        self,
        event_type: type[DomainEvent],
        handler: Callable,
    ) -> None:

        self.handlers[event_type].append(
            handler
        )

    def publish(
        self,
        event: DomainEvent,
    ) -> None:

        event_type = type(event)

        for handler in self.handlers.get(
            event_type,
            [],
        ):
            handler(event)

event_bus = EventBus()
