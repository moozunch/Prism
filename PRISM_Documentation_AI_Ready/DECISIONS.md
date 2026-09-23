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

**Migration strategy:** Retain the existing `Ticket` model, `tickets` table, foreign keys, and related legacy tables as the compatibility and persistence layer. Introduce PRISM terminology first at the Filament/UI and documentation boundary: user-facing labels, navigation, help text, and reports may say `Task`, while internal PHP class names, table names, relationship names, permission keys, and existing route identifiers remain ticket-based until a separately approved migration.

Do not create a parallel `tasks` table or duplicate Ticket/Task models during this phase. Any future full domain rename must be a staged, additive migration with a complete reference inventory, data/backfill plan, foreign-key plan, permission/route compatibility plan, notification update, and regression tests. Existing ticket data and relationships must remain readable throughout the transition.

**Reason:** `Ticket` is referenced by the model graph, migrations, policies, Filament Resources/Pages/Widgets, notifications, exports, and tests. Retaining the storage contract avoids destructive renames and allows PRISM terminology to evolve without breaking existing data or Filament behavior.

**Status:** Accepted. Implementation is deferred to the relevant PRISM task-management phase.

## ADR-006 — JWT statement

**Current source requirement:** Project plan describes JWT authentication.

**Current base:** DewaKoding is a Laravel/Filament application with its own authentication flow.

**Decision:** Retain Laravel/Filament session authentication for the PRISM web application. Do not replace the panel's session, CSRF, email verification, password reset, or profile flow with JWT, and do not add a JWT package or guard as part of PRISM web development.

JWT may be considered later only for a separately approved API or external client requirement. Such an API would be an additional boundary and must not destabilize the existing web authentication flow. The user's own email remains the login identity; organization contact email remains organization profile data.

**Reason:** The current Laravel/Filament session architecture already supports the web application, verified users, hashed passwords, profile management, and Shield authorization. A JWT replacement would add migration and security risk without a confirmed web requirement.

**Status:** Accepted. API authentication, if needed later, requires a separate architecture decision.

## ADR-009 — PRISM role mapping

**Decision:** Reuse Spatie Permissions and Filament Shield as PRISM's server-side authorization mechanism. Map the PRISM business roles as follows:

| PRISM role | Current/target Shield role | Baseline position |
|---|---|---|
| Organization Admin | `admin` initially; refine permissions during organization-management work | Existing `admin` is the closest business-role baseline. It currently receives most generated resource permissions and must not be assumed to have the final PRISM capability matrix. |
| Division Head | New Shield role `division_head` | Missing. Scope must be implemented around the member's assigned division after Division exists. |
| Project Manager | New Shield role `project_manager` | Missing. Scope must be implemented around project management and assigned project membership/responsibility. |
| Member | Existing `member` | Existing role is the closest baseline and currently has limited view/update permissions. Its final task/comment/project capabilities require review. |

Keep `super_admin` as a deployment/bootstrap and technical recovery role during migration, but do not treat it as one of the four PRISM business roles. Its unrestricted behavior must not be used to define normal Organization Admin access. The existing `admin` role should be renamed only through an explicit compatibility plan, if at all; first establish the PRISM capability matrix and migrate assignments safely.

Permissions remain server-side and must be enforced in policies, Filament actions, custom Pages, and query scopes. UI visibility alone is insufficient. Division Head and Project Manager access must be constrained by their organization-local division/project scope, while the single-deployment architecture provides database isolation between organizations.

**Reason:** Existing Shield/Spatie infrastructure is reusable, but the seeded roles (`super_admin`, `admin`, `member`) do not exactly represent PRISM's required roles. Two new scoped roles are needed, and the broad `super_admin` role must remain an operational exception rather than a business authorization model.

**Status:** Accepted as the migration mapping. Detailed permission capabilities and role assignment workflows are implementation prerequisites for the organization-structure phase.

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
