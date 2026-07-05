import time

from app.domain.pipeline_result import PipelineResult

class IncidentPipeline:

    def __init__(self):
        self.stages = []

    def add_stage(
        self,
        stage,
    ):

        self.stages.append(stage)

    # Execute all stages

    def execute(
        self,
        context,
    ):

        started = time.perf_counter()
        results = []
        warnings = []
        errors = []

        for stage in self.stages:

            context.current_stage = (stage.__class__.__name__)

            result = stage.execute(context)

            results.append(result)

            context.execution_trace.append(
                {
                    "stage": result.stage_name,
                    "success": result.success,
                    "time_ms": result.execution_time_ms,
                    "message": result.message,
                }
            )

            if result.warnings:

                warnings.extend(
                    result.warnings
                )

            if not result.success:

                errors.append(
                    result.message
                )

                print("\nPIPELINE EXECUTION TRACE")
                for step in context.execution_trace:
                    print(step)

                return PipelineResult(

                    success=False,
                    context=context,
                    execution_time_ms=
                        (
                            time.perf_counter()
                            -
                            started
                        )
                        *
                        1000,

                    stages=results,
                    warnings=warnings,
                    errors=errors,
                )

            if result.stop_pipeline:

                break

        print("\nPIPELINE EXECUTION TRACE")
        for step in context.execution_trace:
            print(step)

        return PipelineResult(

            success=True,
            context=context,
            execution_time_ms=
                (
                    time.perf_counter()
                    -
                    started
                )
                *
                1000,

            stages=results,
            warnings=warnings,
            errors=errors,
        )

