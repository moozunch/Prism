# AGENTS.md — PRISM Coding Rules

## Role

You are modifying the existing DewaKoding Project Management Laravel application into **PRISM – Project Management System**.

Do not treat DewaKoding's existing domain model as the final PRISM specification.

## Required context

Before changing code:

1. Read `docs/PRISM_REQUIREMENTS.md`.
2. Read `docs/ARCHITECTURE.md` for architecture-related tasks.
3. Read `docs/DEWAKODING_BASELINE.md` before replacing an existing DewaKoding feature.
4. Read `docs/MIGRATION_PLAN.md` when the task changes multiple modules.
5. Read `docs/DECISIONS.md` before making an architectural assumption.

Do **not** load every documentation file for every task. Read only the files relevant to the requested change.

## Core constraints

- Deployment model: **1 Organization = 1 Deployment = 1 Database**.
- Do not introduce shared multi-tenancy unless explicitly requested.
- Do not introduce an organization_id-based tenant architecture merely because the word "organization" exists.
- No cross-organization data access is required because each organization has its own deployment/database.
- User authentication uses the user's own email.
- Organization email is organization profile/contact data, not the user's login identity.
- Domain restriction may be configurable, but must not be assumed mandatory unless the requirement says so.
- Roles and permissions must be enforced server-side, not only hidden in UI.
- Google Calendar and Google Drive are integrations, not replacements for PRISM's core project/task data.
- Third-party integration failures must not delete or corrupt core PRISM data.
- Avoid adding unapproved features.

## Safe modification strategy

Prefer this sequence:

1. Inspect existing implementation.
2. Identify reusable code.
3. Identify code that conflicts with PRISM.
4. Make the smallest coherent change.
5. Add/update migration/model/resource/policy/tests together.
6. Run the narrowest relevant test first.
7. Run broader tests after the focused test passes.
8. Report files changed and verification performed.

Do not perform a large blind rewrite.

## Database rules

- Never modify an existing migration destructively when a new migration is safer.
- Preserve existing data when practical.
- If a field/table is obsolete, document the migration strategy before deleting it.
- Foreign keys and relationships must reflect PRISM's domain model.
- Do not create duplicate concepts with different names unless migration compatibility requires it.

## Naming

Use PRISM terminology in new code and UI:

- Organization
- Division
- Member
- Role
- Project
- Project Team
- Task
- Assignment
- Deadline
- Priority
- Comment
- Attachment
- Activity History
- Notification
- Dashboard
- Reporting
- Google Calendar
- Google Drive

Where legacy DewaKoding uses `Ticket`, determine whether it should become `Task` before changing it. Do not blindly rename every occurrence; inspect relationships, migrations, policies, resources, tests, and UI first.

## Authentication and authorization

DewaKoding currently uses Filament and Filament Shield/RBAC. Preserve the framework's normal authentication flow unless a requirement explicitly changes it.

The project plan mentions hashed passwords, JWT authentication, and role-based authorization. Do not invent a JWT implementation if the existing application architecture does not require it. Flag this as an architectural decision if implementation needs clarification.

## UI

Use existing Filament conventions and components where practical. Avoid introducing a second frontend framework unless explicitly approved.

## Integrations

For Google Calendar/Drive:

- Credentials are deployment-specific.
- Authorization is required.
- Configuration must be disableable/changeable.
- Integration errors must be handled visibly.
- Core PRISM data must remain safe if Google services fail.
- Never commit secrets.

## Testing

For each feature, verify at minimum:

- happy path
- validation failure
- authorization failure
- missing data/not-found case
- relevant integration failure
- regression of existing behavior

## Output discipline

When responding to a coding task:

- State what you changed.
- State what you intentionally did not change.
- State tests/checks run.
- Mention unresolved assumptions.
- Do not dump large unrelated files into the response.
