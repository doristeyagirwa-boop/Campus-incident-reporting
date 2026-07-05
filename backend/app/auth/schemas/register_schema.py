from pydantic import BaseModel
from pydantic import EmailStr

class RegisterSchema(
    BaseModel,
):

    institution_id: str
    full_name: str
    email: EmailStr
    password: str
    role: str
