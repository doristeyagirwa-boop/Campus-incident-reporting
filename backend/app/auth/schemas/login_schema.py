from pydantic import BaseModel
from pydantic import EmailStr

class LoginSchema(
    BaseModel,
):

    institution_id: str
    email: EmailStr
    password: str
