from app.domain.decision_pipeline import (
    DecisionPipeline,
)


class PipelineExecutor:

    @staticmethod
    def execute(
        pipeline,
        stages,
    ):

        for stage in stages:

            result = stage.run(
                pipeline
            )

            pipeline.decisions.append(
                result
            )

        return pipeline
