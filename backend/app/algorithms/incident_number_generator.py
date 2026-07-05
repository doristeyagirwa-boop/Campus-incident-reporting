from datetime import datetime
import hashlib


class IncidentNumberGenerator:

    @staticmethod
    def generate(
        category: str,
        sequence: int,
    ) -> str:

        year = datetime.utcnow().year

        prefix = category[:3].upper()

        checksum_source = f"{year}{sequence}{prefix}"

        checksum = hashlib.sha1(
            checksum_source.encode()
        ).hexdigest()[:4].upper()

        return (
            f"CIR-"
            f"{prefix}-"
            f"{year}-"
            f"{sequence:08d}-"
            f"{checksum}"
        )
