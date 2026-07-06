from app.services.orchestration.stages.classification_stage import (
    ClassificationStage,
)

from app.services.orchestration.stages.duplicate_detection_stage import (
    DuplicateDetectionStage,
)

from app.services.orchestration.stages.risk_stage import (
    RiskStage,
)

from app.services.orchestration.stages.priority_stage import (
    PriorityStage,
)


PIPELINE_STAGES = [

    ClassificationStage,

    DuplicateDetectionStage,

    RiskStage,

    PriorityStage,

]
