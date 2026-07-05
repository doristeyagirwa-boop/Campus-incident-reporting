from argon2 import PasswordHasher
from argon2.exceptions import VerifyMismatchError


class PasswordHasherService:

    def __init__(self):

        self.hasher = PasswordHasher(

            time_cost=4,

            memory_cost=65536,

            parallelism=4,

            hash_len=32,

            salt_len=16,
        )

    def hash(
        self,
        password: str,
    ) -> str:

        return self.hasher.hash(password)

    def verify(
        self,
        hashed_password: str,
        plain_password: str,
    ) -> bool:

        try:

            return self.hasher.verify(
                hashed_password,
                plain_password,
            )

        except VerifyMismatchError:

            return False


password_hasher = PasswordHasherService()
