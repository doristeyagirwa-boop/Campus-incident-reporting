from pydantic import BaseModel


class StatusUpdateSchema(
    BaseModel
):

    target_status: str

    actor: str
