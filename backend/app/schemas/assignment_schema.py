from pydantic import BaseModel

class AssignmentSchema(BaseModel):
    technician_id:str
    actor:str
    reason:str="MANUAL"
