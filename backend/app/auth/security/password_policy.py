import re


class PasswordPolicy:

    def validate(
        self,
        password,
    ):

        if len(password) < 12:

            raise ValueError(
                "Password must contain at least 12 characters."
            )

        if not re.search(
            r"[A-Z]",
            password,
        ):

            raise ValueError(
                "Missing uppercase letter."
            )

        if not re.search(
            r"[a-z]",
            password,
        ):

            raise ValueError(
                "Missing lowercase letter."
            )

        if not re.search(
            r"\d",
            password,
        ):

            raise ValueError(
                "Missing digit."
            )

        if not re.search(
            r"[!@#$%^&*(),.?\":{}|<>]",
            password,
        ):

            raise ValueError(
                "Missing special character."
            )


password_policy = PasswordPolicy()
