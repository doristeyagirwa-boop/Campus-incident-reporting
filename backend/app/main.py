from fastapi import FastAPI

from app.api.router import api_router

from app.core.exception_handlers import register_exception_handlers

from app.db.session import engine

from app.db.base import Base

app = FastAPI(
    title="Insitutional Incident Reporting Platform",

    version="1.0.0",

    description=
        (
            "Enterprise Incident Management "
            "Platform with intelligent "
            "decision pipelines, "
            "event-driven architecture, "
            "institutional governance, "
            "SLA automation, "
            "audit logging, "
            "risk intelligence "
            "and operational analytics."
        ),

    docs_url="/docs",
    redoc_url="/redoc",
    openapi_url="/openapi.json",
)

register_exception_handlers(
    app
)

@app.on_event(
    "startup",
)
def startup():
    Base.metadata.create_all(
        bind=engine,
    )

app.include_router(
    api_router,
)
