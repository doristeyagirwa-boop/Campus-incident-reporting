class EventStore:

    def __init__(
        self,
        history_repository,
    ):
        self.history_repository = (
            history_repository
        )

    def append(
        self,
        incident_id,
        event_type,
        actor,
        old_value=None,
        new_value=None,
    ):

        self.history_repository.create_event(
            incident_id=incident_id,
            event_type=event_type,
            performed_by=actor,
            old_value=old_value,
            new_value=new_value,
        )
