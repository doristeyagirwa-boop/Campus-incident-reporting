from dataclasses import dataclass


@dataclass
class IncidentReadModel:

    incident: object

    history: list

    comments: list

    attachments: list

    analytics: dict

    compliance: list
