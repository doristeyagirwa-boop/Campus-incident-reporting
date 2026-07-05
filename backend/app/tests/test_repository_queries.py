from app.db.session import SessionLocal

from app.repositories.incident_repository import (
    IncidentRepository,
)


def run():

    db = SessionLocal()

    repository = (
        IncidentRepository(db)
    )

    print(
        "TOTAL:",
        repository.count_all()
    )

    print(
        "SEARCH:",
        repository.search(
            "WiFi"
        )
    )

    print(
        "STATUS:",
        repository.get_by_status(
            "SUBMITTED"
        )
    )

    db.close()


if __name__ == "__main__":
    run()
