class IncidentDomainException(Exception):

    pass


class IncidentNotFound(

    IncidentDomainException

):

    pass


class InvalidStatusTransition(

    IncidentDomainException

):

    pass


class ValidationException(

    IncidentDomainException

):

    pass


class DuplicateIncidentException(

    IncidentDomainException

):

    pass
