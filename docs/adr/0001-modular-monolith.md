# ADR 0001: Modular Monolith Architecture
**Status:** Accepted
**Date:** 2026-10-25

## Context
The system serves 4 retail stores in Samarinda with inventory, POS, purchasing, and double-entry accounting. Transactions frequently span multiple domains (e.g., a sale affects stock, accounting journals, and payments simultaneously) and require ACID guarantees. The team is small and operates as a single unit.

## Decision
Build a modular monolith: a single deployable Laravel application organized into domain modules under `app/Domain/` (Inventory, Accounting, Sales, Purchasing, Reporting, Tax), each with clear boundaries. Modules communicate through service classes and events, not direct cross-model queries.

## Consequences
**Positive:**
- Single database transaction spans all domains — no distributed transaction complexity.
- Simpler deployment, monitoring, and debugging compared to microservices.
- Easier refactoring: module boundaries can be strengthened over time without full microservice extraction.
- One CI/CD pipeline, one team, lower operational overhead.

**Negative:**
- All modules scale together; no independent scaling per domain.
- Risk of boundary erosion if Eloquent models are used directly across domains without discipline.
- Mitigation: enforce module boundaries via Larastan/Architecture tests (Pest `arch()`) and codebase-memory-mcp graph checks in CI.
