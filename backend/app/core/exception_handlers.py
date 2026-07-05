from fastapi import FastAPI
from fastapi import Request
from fastapi.responses import JSONResponse

async def runtime_exception_handler(
    request: Request,
    exc: RuntimeError,
):

    return JSONResponse(

        status_code=400,

        content={

            "success": False,
            "message": str(exc),
        },
    )


async def value_exception_handler(
    request: Request,
    exc: ValueError,
):

    return JSONResponse(

        status_code=400,

        content={

            "success": False,
            "message": str(exc),
        },
    )


def register_exception_handlers(
    app: FastAPI,
):

    app.add_exception_handler(
        RuntimeError,
        runtime_exception_handler,
    )

    app.add_exception_handler(
        ValueError,
        value_exception_handler,
    )
