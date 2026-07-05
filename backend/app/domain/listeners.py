from app.domain.events import (
    CommentAddedEvent,
    IncidentCreatedEvent,
    StatusChangedEvent,
)

from app.domain.mappers.history_mapper import HistoryEventMapper
from app.domain.mappers.audit_mapper import AuditEventMapper
from app.domain.mappers.notification_mapper import NotificationEventMapper

from app.domain.mappers.projection_mapper import ProjectionMapper

from app.notifications.notification_service import NotificationService

class HistoryListener:

    def __init__(self,repository):
        self.repository = repository

    def __call__(self,event):

        # Persist history

        HistoryEventMapper.to_repository_call(
            repository=self.repository,
            event=event,
        )

class AuditListener:

    def __init__(self,audit_service):
        self.audit_service = audit_service

    def __call__(self,event):

        # Persist audit

        AuditEventMapper.to_service_call(
            audit_service=self.audit_service,
            event=event,
        )

class NotificationListener:

    def __init__(self,notification_service):
        self.notification_service=notification_service

    def __call__(self,event):

        NotificationEventMapper.to_service_call(
            notification_service=self.notification_service,
            event=event,
        )

class ProjectionListener:

    def __init__(self,projection):
        self.projection=projection

    def __call__(self,event):

        ProjectionMapper.update(
            self.projection,
            event,
        )
