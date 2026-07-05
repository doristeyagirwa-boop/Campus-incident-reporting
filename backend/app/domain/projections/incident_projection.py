import logging

logger=logging.getLogger(__name__)

class IncidentProjection:

    def __init__(self):
        self.cache={}

    def update(
        self,
        event,
    ):

        incident=(
            self.cache.setdefault(
                str(event.incident_id),
                {},
            )
        )

        event_name=type(event).__name__

        if event_name=="IncidentCreatedEvent":

            incident.update({

                "incident_id":
                    str(event.incident_id),

                "incident_number":
                    event.incident_number,

                "category":
                    event.category,

                "priority":
                    event.priority,

                "status":
                    "OPEN",

            })

        elif event_name=="StatusChangedEvent":

            incident["status"]=(
                event.new_status
            )

        elif event_name=="TechnicianAssignedEvent":

            incident["assigned_technician"]=(
                event.technician_id
            )

        elif event_name=="PriorityChangedEvent":

            incident["priority"]=(
                event.new_priority
            )

        logger.info(
            "Projection updated: %s",
            event_name,
        )

    def get(
        self,
        incident_id,
    ):

        return self.cache.get(
            str(incident_id),
        )

    def all(self):

        return list(
            self.cache.values()
        )
