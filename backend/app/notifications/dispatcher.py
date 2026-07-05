class NotificationDispatcher:

    def __init__(self,*channels):
        self.channels=channels

    def dispatch(
        self,
        title,
        message,
        recipients=None,
    ):

        for channel in self.channels:

            channel.send(
                title,
                message,
                recipients,
            )
