# DewaKoding Baseline

## Purpose

This file records what the selected open-source base already provides before PRISM modification.

Repository:
https://github.com/SeptiawanAjiP/dewakoding-project-management

The repository README currently identifies it as a Laravel Filament 4 project-management application.

## Existing capabilities relevant to PRISM

- Project management
- Role-based access control using Filament Shield
- Team member management with role assignments
- Ticket statuses
- Ticket management
- Assignees
- Due dates
- Unique ticket identifiers
- Epics
- Comments
- Kanban board
- Multi-user assignment
- Contribution chart
- Timeline
- CSV export
- Leaderboard
- External dashboard/client portal
- Google OAuth login
- Queue/email notifications

The repository README also documents Laravel 12, PHP 8.2+, MySQL/PostgreSQL support, and standard Laravel installation.

## Existing capabilities that need review before keeping

### Ticket

DewaKoding calls the main work item a `Ticket`. PRISM calls it a `Task`.

Do not perform a global string replacement.

Inspect:

- model
- migration
- relationships
- Filament Resource
- policies
- routes
- notifications
- comments
- tests
- database foreign keys

Then choose one of:

1. true rename/migration
2. internal Ticket model retained but PRISM UI says Task
3. compatibility layer during transition

### Epic

PRISM does not list Epic Management as a core requirement.

Do not delete immediately. First check whether the project can reuse Epic functionality as an internal grouping concept. If it is not needed, mark it for later removal.

### Leaderboard

Not in the PRISM scope.

Treat as optional/legacy until a project decision is made.

### Client Portal

Not in the PRISM scope.

Treat as optional/legacy until a project decision is made.

### CSV export

Not explicitly required in the PRISM SRS.

Do not advertise it as a core requirement unless approved.

### Google OAuth login

DewaKoding supports Google OAuth login. PRISM's Google Calendar/Drive integration is a separate requirement.

Do not confuse:

- Google OAuth login
- Google Calendar integration
- Google Drive integration

They are different capabilities.

### Queue/email notifications

This is valuable reusable infrastructure for PRISM Notification Management.

Reuse it where possible.

## License

The repository README states that DewaKoding Project Management is licensed under the MIT License.

Preserve required license/copyright notices when modifying and redistributing the codebase.
