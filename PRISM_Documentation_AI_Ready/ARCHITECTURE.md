# PRISM Architecture

## 1. Architectural direction

PRISM is built by modifying the DewaKoding Project Management Laravel application.

Current DewaKoding repository characteristics verified from its README:

- Laravel 12
- PHP 8.2+
- Filament 4
- MySQL 8+ / PostgreSQL 12+
- Filament Shield for role-based access control
- project/ticket management
- team members and role assignments
- comments
- Kanban
- timeline
- queue/email notifications
- Google OAuth login
- Docker/standard Laravel deployment support

Source:
https://github.com/SeptiawanAjiP/dewakoding-project-management

## 2. Deployment architecture

```text
Organization A
    |
    +-- PRISM Application A
    |
    +-- Database A

Organization B
    |
    +-- PRISM Application B
    |
    +-- Database B
```

There is no shared database between organizations.

### Important consequence

Do not introduce:

- tenant switching
- organization_id as the main isolation mechanism
- cross-organization queries
- shared organization tables across deployments

An organization record may still exist inside its own deployment because PRISM needs Organization Management. That record represents the current deployment's organization; it is not a tenant discriminator.

## 3. Conceptual domain

```text
Organization
  |
  +-- Divisions
  |
  +-- Members
        |
        +-- Roles / Permissions
  |
  +-- Projects
        |
        +-- Project Team
        |
        +-- Tasks
              |
              +-- Assignments
              +-- Deadline
              +-- Priority
              +-- Status
              +-- Comments
              +-- Attachments
              +-- Activity History
  |
  +-- Notifications
  |
  +-- Dashboard / Reporting
  |
  +-- Integration Configuration
        |
        +-- Google Calendar
        +-- Google Drive
```

## 4. Application architecture direction

Prefer existing Laravel + Filament architecture.

Use existing:

- Eloquent models
- migrations
- Filament Resources
- Filament Pages/Widgets
- policies/permissions
- notifications
- queues
- storage

Avoid introducing a second application architecture unless necessary.

## 5. Legacy-to-PRISM conceptual mapping

| DewaKoding | PRISM | Action |
|---|---|---|
| User | Member | Extend/reuse |
| Role/Permission | Role & Authorization | Reuse Shield |
| Project | Project | Reuse/modify |
| Project Member | Project Team | Reuse/modify |
| Ticket | Task | Migrate terminology/data model safely |
| Ticket Status | Task Status | Reuse concept |
| Assignee | Assignment | Reuse concept |
| Due Date | Deadline | Rename in UI/domain where safe |
| Priority | Priority | Reuse |
| Comment | Comment | Reuse |
| Attachment | Attachment | Reuse/extend |
| Timeline | Project Monitoring / Activity | Evaluate and reuse |
| Board/Kanban | Task Monitoring | Reuse if within scope |
| Contributions chart | Dashboard | Reuse if useful and requirement-aligned |
| Email notifications | Notification Management | Reuse |
| Google OAuth login | Authentication option | Keep separate from Calendar/Drive integration |

## 6. Authentication decision

The PRISM project plan states JWT authentication, while the DewaKoding base is a Laravel/Filament application.

Therefore:

- do not blindly add a separate JWT authentication layer
- inspect the current authentication implementation first
- determine whether PRISM requires API JWT authentication or only application authentication
- record the final decision in `docs/DECISIONS.md`

For the user-facing requirement, the user's own email is the login identity.

## 7. Integration architecture

```text
PRISM
  |
  +-- Integration Configuration
  |
  +-- Google Calendar Adapter
  |       |
  |       +-- Google Calendar API
  |
  +-- Google Drive Adapter
          |
          +-- Google Drive API
```

Integration adapters should isolate third-party logic from core project/task logic.

Core data should be saved independently of third-party service success where possible.

## 8. Database strategy

Expected core concepts:

- users/members
- roles
- permissions
- organization
- divisions
- projects
- project team membership
- tasks
- task assignments
- statuses
- comments
- attachments
- activity records
- notifications
- integration configuration

Do not create final migrations solely from this conceptual list. First inspect the DewaKoding schema and then implement the smallest safe migration path.

## 9. Change safety

Before changing a model/table:

1. inspect migration
2. inspect model
3. inspect relationships
4. inspect Filament Resource/Page
5. inspect policies
6. inspect tests
7. inspect usages/search references
8. plan migration
9. implement
10. test

