# PRISM Architecture Decisions

This file prevents the coding agent from repeatedly re-deciding the same issue.

## ADR-001 — Deployment model

**Decision:** 1 Organization = 1 Deployment = 1 Database.

**Reason:** This is the approved PRISM project model.

**Consequence:** Do not implement shared multi-tenancy as a core architecture.

**Status:** Accepted.

## ADR-002 — DewaKoding as base

**Decision:** DewaKoding Project Management is the starting codebase.

**Reason:** It already contains project/task-like management, RBAC, comments, team members, Kanban, timeline, notifications, and Filament infrastructure.

**Status:** Accepted.

## ADR-003 — Filament remains the application UI framework

**Decision:** Prefer the existing Laravel + Filament architecture.

**Reason:** Rewriting the application into another frontend stack increases risk and consumes project time.

**Status:** Accepted unless implementation audit proves otherwise.

## ADR-004 — Google OAuth is separate from Google Calendar/Drive

**Decision:** Treat login and Google service integrations as separate capabilities.

**Reason:** Google OAuth login authenticates a user; Calendar/Drive integration authorizes access to Google services.

**Status:** Accepted.

## ADR-005 — Ticket → Task

**Decision:** PRISM terminology is `Task`.

**Implementation note:** Do not perform a blind global rename. Choose a migration strategy after inspecting the schema and code references.

**Status:** Accepted conceptually; implementation strategy pending audit.

## ADR-006 — JWT statement

**Current source requirement:** Project plan describes JWT authentication.

**Current base:** DewaKoding is a Laravel/Filament application with its own authentication flow.

**Decision:** Do not introduce JWT blindly. First determine whether JWT is needed for an API/client integration or must replace web authentication.

**Status:** Open.

## ADR-007 — Unapproved features

**Decision:** Features not in PRISM requirements are not automatically part of PRISM.

Examples:

- leaderboard
- client portal
- epic management
- mandatory CSV/PDF/Excel exports
- advanced AI

**Status:** Accepted.

## ADR-008 — Integration failure isolation

**Decision:** Google Calendar/Drive failures must not destroy core PRISM project/task data.

**Status:** Accepted.
