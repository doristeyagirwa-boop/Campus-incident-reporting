import hashlib
import secrets

class SecureTokenGenerator:

    def generate(self):
        token = secrets.token_urlsafe(64)
        hashed = hashlib.sha256(

            token.encode()

        ).hexdigest()

        return token, hashed

token_generator = SecureTokenGenerator()
