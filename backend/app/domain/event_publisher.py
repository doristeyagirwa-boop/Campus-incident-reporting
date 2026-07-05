from collections import defaultdict

import logging

class EventPublisher:

    def __init__(self):
        self._listeners = defaultdict(list)

    def subscribe(
        self,
        event_type,
        listener,
    ):

        self._listeners[
            event_type
        ].append(
            listener
        )

    def publish(
        self,
        event,
    ):

        # Notify specific subscribers

        listeners = (self._listeners.get(
                type(event),
                [],
            )
        )

        logging.info(
            "Publishing %s to %d listeners",
            type(event).__name__,
            len(listeners),
        )

        for listener in listeners:
            listener(event)

        # Notify global subscribers

        for listener in self._listeners.get(
            object,
            [],
        ):

            listener(event)
