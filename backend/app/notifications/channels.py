class NotificationChannel:

    def send(
        self,
        title,
        message,
        recipients,
    ):
        raise NotImplementedError()


class EmailChannel(NotificationChannel):

    def send(
        self,
        title,
        message,
        recipients,
    ):
        pass


class SMSChannel(NotificationChannel):

    def send(
        self,
        title,
        message,
        recipients,
    ):
        pass


class PushChannel(NotificationChannel):

    def send(
        self,
        title,
        message,
        recipients,
    ):
        pass


class TeamsChannel(NotificationChannel):

    def send(
        self,
        title,
        message,
        recipients,
    ):
        pass
