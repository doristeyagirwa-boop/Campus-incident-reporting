class ClassificationEngine:

    NETWORK_KEYWORDS = {

        "wifi",
        "network",
        "switch",
        "router",
    }

    SECURITY_KEYWORDS = {

        "breach",
        "hack",
        "phishing",
        "malware",
    }

    @classmethod
    def classify(
        cls,
        text,
    ):

        text = text.lower()

        for keyword in (
            cls.NETWORK_KEYWORDS
        ):

            if keyword in text:

                return "NETWORK"

        for keyword in (
            cls.SECURITY_KEYWORDS
        ):

            if keyword in text:

                return "SECURITY"

        return "GENERAL"
