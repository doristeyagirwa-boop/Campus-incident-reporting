class EventListener:

    def supports(
        self,
        event,
    ) -> bool:
        raise NotImplementedError

    def handle(
        self,
        event,
    ):
        raise NotImplementedError

    def __call__(
        self,
        event,
    ):

        if self.supports(event):
            self.handle(event)
