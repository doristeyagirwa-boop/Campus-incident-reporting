from app.algorithms.assignment_engine import AssignmentEngine
from app.algorithms.priority_engine import PriorityEngine
from app.algorithms.risk_engine import RiskEngine
from app.algorithms.similarity_engine import SimilarityEngine

from app.notifications.notification_service import NotificationService

from app.events.event_bus import event_bus

from app.ai.classification_engine import ClassificationEngine

from app.algorithms.sla_engine import SLAEngine

from app.registry.technician_registry import TechnicianRegistry
from app.registry.asset_registry import AssetRegistry
from app.registry.location_registry import LocationRegistry
from app.registry.policy_registry import PolicyRegistry
from app.registry.risk_profile_registry import RiskProfileRegistry
from app.registry.sla_registry import SLA_REGISTRY
from app.notifications.dispatcher import NotificationDispatcher

from app.algorithms.workload_engine import WorkloadEngine

from app.domain.policies.assignment_policy import AssignmentPolicy

from app.auth.security.password_hasher import password_hasher
from app.auth.security.jwt_service import jwt_service
from app.auth.security.token_generator import token_generator
from app.auth.security.password_policy import password_policy

class ServiceContainer:

    _instance = None

    def __new__(cls):

        if cls._instance is None:
            cls._instance = super().__new__(cls)

        return cls._instance

    def __init__(self):

        if getattr(
            self,
            "_initialized",
            False,
        ):
            return

        self.assignment_engine = AssignmentEngine()

        self.priority_engine = PriorityEngine()

        self.risk_engine = RiskEngine()

        self.similarity_engine = SimilarityEngine()

        self.classification_engine = ClassificationEngine()

        self.sla_engine = SLAEngine()

        self.notification_dispatcher = (
            NotificationDispatcher()
        )

        self.notification_service = (
            NotificationService(
                self.notification_dispatcher,
            )
        )

        self.event_bus = event_bus

        self._initialized = True

        self.technician_registry = TechnicianRegistry()

        self.asset_registry = AssetRegistry()

        self.location_registry = LocationRegistry()

        self.policy_registry = PolicyRegistry()

        self.risk_profile_registry = RiskProfileRegistry()

        self.sla_registry = SLA_REGISTRY

        self.workload_engine=(
            WorkloadEngine()
        )

        self.assignment_policy=(
            AssignmentPolicy(
                self.workload_engine,
            )
        )

        # Authentication Security

        self.password_hasher = (
            password_hasher
        )

        self.jwt_service = (
            jwt_service
        )

        self.token_generator = (
            token_generator
        )

        self.password_policy = (
            password_policy
        )
