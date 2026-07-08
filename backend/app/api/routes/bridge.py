from __future__ import annotations

from pydantic import BaseModel, Field
from fastapi import APIRouter

router = APIRouter(prefix="/bridge", tags=["PHP Bridge"])


class BridgeAnalyzeRequest(BaseModel):
    title: str = Field(default="", max_length=255)
    description: str = Field(default="", max_length=5000)
    category: str = Field(default="", max_length=120)
    location: str = Field(default="", max_length=255)


class BridgeAnalyzeResponse(BaseModel):
    domain: str
    priority_hint: str
    risk_hint: int
    recommended_action: str
    confidence: float
    matched_terms: list[str]


def _contains_any(text: str, terms: list[str]) -> list[str]:
    lowered = text.lower()
    return [term for term in terms if term in lowered]


@router.post("/analyze", response_model=BridgeAnalyzeResponse)
def analyze_incident(payload: BridgeAnalyzeRequest) -> BridgeAnalyzeResponse:
    text = " ".join(
        [
            payload.title,
            payload.description,
            payload.category,
            payload.location,
        ]
    ).lower()

    rules = [
        (
            "Finance",
            ["fee", "balance", "payment", "clearance", "arrears", "exam card", "invoice"],
            "Finance should review student account clearance.",
        ),
        (
            "Academics",
            ["attendance", "grade", "marks", "exam", "cat", "lecture", "class", "registrar"],
            "Academic office should review eligibility, attendance, or course records.",
        ),
        (
            "Dining",
            ["food", "rice", "beans", "cafe", "cafeteria", "kitchen", "meal", "lunch"],
            "Dining team should review stock or service issue.",
        ),
        (
            "Facilities",
            ["room", "socket", "power", "projector", "chair", "desk", "door", "light"],
            "Facilities team should inspect and resolve the physical space issue.",
        ),
        (
            "IT",
            ["wifi", "network", "computer", "login", "system", "printer", "internet"],
            "Technical team should diagnose and resolve the IT issue.",
        ),
        (
            "Security",
            ["theft", "fight", "threat", "unsafe", "security", "assault", "fire"],
            "Security team should review the safety risk immediately.",
        ),
    ]

    best_domain = "General"
    best_terms: list[str] = []
    recommended_action = "Institutional desk should review and assign the issue."

    for domain, terms, action in rules:
        matched = _contains_any(text, terms)
        if len(matched) > len(best_terms):
            best_domain = domain
            best_terms = matched
            recommended_action = action

    risk = min(95, 20 + (len(best_terms) * 15))

    if any(term in text for term in ["urgent", "danger", "fire", "threat", "attack", "critical"]):
        risk = max(risk, 85)

    if risk >= 80:
        priority = "High"
    elif risk >= 50:
        priority = "Medium"
    else:
        priority = "Low"

    confidence = min(0.95, 0.45 + (len(best_terms) * 0.12))

    return BridgeAnalyzeResponse(
        domain=best_domain,
        priority_hint=priority,
        risk_hint=risk,
        recommended_action=recommended_action,
        confidence=round(confidence, 2),
        matched_terms=best_terms,
    )
