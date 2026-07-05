import sys
from pathlib import Path

ROOT_DIR = Path(__file__).resolve().parents[1]

if str(ROOT_DIR) not in sys.path:
    sys.path.insert(
        0,
        str(ROOT_DIR),
    )

from app.auth.models.role import Role
from app.db.session import SessionLocal


DEFAULT_ROLES = [
    (
        "STUDENT",
        "Student reporter account.",
    ),
    (
        "STAFF",
        "General institutional staff account.",
    ),
    (
        "TECHNICIAN",
        "Technician account for assigned operational work.",
    ),
    (
        "INVESTIGATOR",
        "Incident investigator account.",
    ),
    (
        "DEAN",
        "Dean or senior institutional authority.",
    ),
    (
        "SECURITY",
        "Security operations account.",
    ),
    (
        "ADMIN",
        "Administrative operator account.",
    ),
    (
        "SUPER_ADMIN",
        "System-wide administrator account.",
    ),
]


def main():
    db = SessionLocal()

    try:
        for name, description in DEFAULT_ROLES:
            existing = (
                db.query(Role)
                .filter(Role.name == name)
                .first()
            )

            if existing:
                continue

            db.add(
                Role(
                    name=name,
                    description=description,
                )
            )

        db.commit()

        print("identity seed complete")

    except Exception:
        db.rollback()
        raise

    finally:
        db.close()


if __name__ == "__main__":
    main()
