from dataclasses import dataclass


@dataclass
class SecurityContext:

    user_id: str

    role: str

    department: str


@dataclass
class IncidentResource:

    category: str

    department: str

    confidential: bool = False


class AccessEngine:

    @staticmethod
    def can_view(
        subject: SecurityContext,
        resource: IncidentResource,
    ):

        if subject.role == "ADMIN":
            return True

        if (
            resource.confidential
            and
            subject.role != "INVESTIGATOR"
        ):
            return False

        if (
            subject.department
            !=
            resource.department
        ):
            return False

        return True
