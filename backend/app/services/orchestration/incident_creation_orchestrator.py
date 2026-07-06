from app.domain.pipeline_context import PipelineContext

from app.services.pipeline.pipeline_builder import PipelineBuilder

class IncidentCreationOrchestrator:

    def __init__(
        self,
        context,
        container,
    ):
        self.context = context
        self.container = container


    def create_incident(
        self,
        title,
        description,
        category,
        location,
        reporter_id,
        affected_users,
    ):

        workflow_context = (
            PipelineContext(
                title=title,
                description=description,
                category=category,
                location=location,
                reporter_id=reporter_id,
                affected_users=affected_users,
            )
        )

        pipeline = (
            PipelineBuilder.build(
                context= self.context,
                container= self.container,
            )
        )

        pipeline_result = (
            pipeline.execute(
                workflow_context
            )
        )

        if not pipeline_result.success:
            raise RuntimeError(
                "\n".join(
                    pipeline_result.errors
                )
            )

        workflow_context = (
            pipeline_result.context
        )

        if (
            workflow_context
            .duplicate_found
        ):

            return (
                workflow_context.duplicate_incident,
                None,
                workflow_context,
            )

        incident = workflow_context.incident

        return (
            incident,
            workflow_context,
        )
