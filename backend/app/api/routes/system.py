from fastapi import APIRouter

router = APIRouter(
    prefix="/system",
    tags=["System"],
)


@router.get("/health")
async def health():

    return {

        "status": "healthy",

        "service": "Incident Platform",

        "version": "1.0.0",
    }
