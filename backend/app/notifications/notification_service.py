from app.domain.events import *

class NotificationService:

    def __init__(
        self,
        dispatcher,
    ):
        self.dispatcher=dispatcher

    def incident_created(self,event):
        self.dispatcher.dispatch(
            title="Incident Created",
            message=(
                f"{event.incident_number}"
                " created."
            ),
        )

    def status_changed(self,event):
        self.dispatcher.dispatch(
            title="Status Changed",
            message=(
                f"{event.old_status}"
                " -> "
                f"{event.new_status}"
            ),
        )

    def incident_resolved(self,event):
        self.dispatcher.dispatch(
            title="Incident Resolved",
            message=(
                f"{event.incident_id}"
                " resolved."
            ),
        )

    def incident_closed(self,event):
        self.dispatcher.dispatch(
            title="Incident Closed",
            message=(
                f"{event.incident_id}"
                " closed."
            ),
        )

    def incident_escalated(self,event):
        self.dispatcher.dispatch(
            title="Incident Escalated",
            message=str(
                event.escalation_level
            ),
        )

    def technician_assigned(self,event):
        self.dispatcher.dispatch(
            title="Technician Assigned",
            message=(
                f"{event.previous_technician}"
                " -> "
                f"{event.technician_id}"
            ),
        )

    def comment_added(self,event):
        self.dispatcher.dispatch(
            title="Comment Added",
            message=event.comment,
        )

    def attachment_added(self,event):
        self.dispatcher.dispatch(
            title="Attachment Added",
            message=event.filename,
        )

    def priority_changed(self,event):
        self.dispatcher.dispatch(
            title="Priority Changed",
            message=(
                f"{event.old_priority}"
                " -> "
                f"{event.new_priority}"
            ),
        )

    def risk_recalculated(self,event):
        self.dispatcher.dispatch(
            title="Risk Recalculated",
            message=(
                f"{event.previous_score}"
                " -> "
                f"{event.new_score}"
            ),
        )

    def sla_started(self,event):
        self.dispatcher.dispatch(
            title="SLA Started",
            message="SLA started.",
        )

    def sla_breached(self,event):
        self.dispatcher.dispatch(
            title="SLA Breached",
            message=event.breached_rule,
        )

    def sla_recovered(self,event):
        self.dispatcher.dispatch(
            title="SLA Recovered",
            message="Recovered.",
        )

