# DewaKoding → PRISM Migration Plan

## Goal

Transform the DewaKoding Project Management application into PRISM without rewriting the entire application unnecessarily.

## Phase 0 — Baseline audit

**Do first.**

- install and run DewaKoding
- verify database migration
- verify Filament panel
- create test user
- verify role/permission flow
- inspect current models
- inspect migrations
- inspect Filament Resources
- inspect policies
- inspect notifications/queues
- inspect storage/attachments
- inspect Google OAuth implementation
- run existing tests

Output:

- baseline test result
- model/table inventory
- resource/page inventory
- dependency inventory
- legacy feature list

## Phase 1 — PRISM identity and branding

- rename application branding to PRISM
- update application name
- update navigation labels
- update login/dashboard branding
- update README/environment naming
- retain technical compatibility where needed

Do not change domain data yet.

## Phase 2 — Organization Management

Add/adjust:

- Organization
- Organization Information
- Organization Setup

Because each deployment represents one organization, Organization is a deployment-level configuration, not a tenant-switching mechanism.

## Phase 3 — Organization Structure

Implement:

- Division Management
- Member Management
- Role & Authorization

Reuse Filament Shield/RBAC where possible.

Required roles:

- Organization Admin
- Division Head
- Project Manager
- Member

Verify server-side authorization.

## Phase 4 — Authentication & Profile

Verify:

- user's own email as login identity
- password/security behavior
- profile management
- role assignment
- optional organization domain restriction

Do not make organization email equal to user login email.

Resolve the project-plan JWT statement before introducing a new authentication architecture.

## Phase 5 — Project Management

Adapt:

- Project
- Project Team
- Project Progress

Reuse existing DewaKoding project functionality where it matches requirements.

## Phase 6 — Task Management

Adapt:

- Ticket → Task terminology/domain
- Assignment
- Deadline
- Priority
- Status

Preserve data during migration.

## Phase 7 — Collaboration

Implement/verify:

- Comments
- Attachments
- Activity History

Reuse existing comment/file/timeline mechanisms where appropriate.

## Phase 8 — Notification

Reuse DewaKoding's queue/email notification infrastructure.

Map notifications to PRISM events.

## Phase 9 — Dashboard & Monitoring

Adapt existing:

- dashboard
- contribution chart where useful
- timeline/monitoring
- task/project summaries

Ensure role-based visibility.

## Phase 10 — Reporting

Implement reports based on:

- projects
- project status
- project progress
- tasks
- task status
- deadlines

Add filters supported by SRS.

Do not add mandatory PDF/Excel export without approval.

## Phase 11 — Google Calendar

Add:

- deployment-level integration configuration
- credentials
- authorization
- selected project/task schedule/deadline integration
- success/error handling
- enable/disable configuration

Use an adapter/service boundary.

## Phase 12 — Google Drive

Add:

- deployment-level integration configuration
- credentials
- authorization
- file access
- project/task file references
- success/error handling
- enable/disable configuration

Use an adapter/service boundary.

## Phase 13 — Cleanup

Review legacy features:

- Epic
- Leaderboard
- Client Portal
- CSV export
- other DewaKoding-only concepts

Do not remove anything until:

1. it is confirmed outside PRISM scope
2. dependencies are known
3. migration impact is understood
4. tests are updated

## Phase 14 — Validation

Run:

- functional tests
- RBAC tests
- database tests
- integration tests
- regression tests
- usability checks
- deployment test

## Recommended implementation order

```text
Baseline
  ↓
Branding
  ↓
Organization
  ↓
Division + Member + Role
  ↓
Authentication/Profile
  ↓
Project + Project Team
  ↓
Task + Assignment + Deadline + Priority
  ↓
Collaboration
  ↓
Notification
  ↓
Dashboard/Monitoring
  ↓
Reporting
  ↓
Google Calendar
  ↓
Google Drive
  ↓
Cleanup
  ↓
Full testing
  ↓
Deployment
```
