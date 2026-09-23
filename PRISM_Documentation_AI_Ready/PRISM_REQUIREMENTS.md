# PRISM Functional Requirements

## 1. Product definition

PRISM (Project Management System) is a reusable and configurable project-management application intended to be deployed independently for different organizations.

### Deployment model

> **1 Organization = 1 Deployment = 1 Database**

Each organization has its own application environment and database. PRISM is therefore **not** designed as a shared multi-tenant SaaS database.

## 2. Target users

- Organization Admin
- Division Head
- Project Manager
- Member

## 3. Core modules

```text
PRISM
├── Authentication & Profile Management
├── Organization Management
│   ├── Organization Setup
│   └── Organization Information
├── Organization Structure
│   ├── Division Management
│   ├── Member Management
│   └── Role & Authorization
├── Project Management
│   ├── Project
│   ├── Project Team
│   └── Project Progress
├── Task Management
│   ├── Task
│   ├── Assignment
│   ├── Deadline
│   └── Priority
├── Collaboration
│   ├── Comments
│   ├── Attachments
│   └── Activity History
├── Monitoring
│   ├── Dashboard
│   ├── Notification
│   └── Reporting
└── System Integration
    ├── Google Calendar
    └── Google Drive
```

## 4. Organization Management

### Organization Setup

Organization Admin performs initial configuration:

- organization name
- organization information
- organization contact information
- division setup
- member setup
- role/permission configuration

The organization email is profile/contact information. It is not automatically the login email for every user.

### Organization Information

The system stores and displays organization information according to the organization's configuration.

## 5. Division Management

The system must support:

- create division
- view division
- update division
- delete/deactivate division when allowed
- associate members with divisions
- prevent invalid references to deleted divisions

The exact division hierarchy is not defined as a multi-level tree requirement.

## 6. Member Management

The system must support:

- add member
- edit member profile
- assign division
- assign role
- activate/deactivate member where allowed
- view member information
- enforce authorization based on role

A member's own email is the user's identity/login identifier.

## 7. Role & Authorization

Required role concepts:

- Organization Admin
- Division Head
- Project Manager
- Member

Permissions must be enforced server-side.

Visibility should follow authorization. A user must not access another user's/project's protected data by manually changing a URL or request.

## 8. Project Management

The system must support:

- create project
- edit project
- view project
- project status
- project start date
- project deadline
- project team
- project progress
- project-related task data

A project is the primary container for project work.

## 9. Project Team

The system must support:

- add project members
- remove project members where allowed
- assign project responsibilities/roles where defined
- use project membership when determining project access

## 10. Project Progress

The system must show current project progress based on project/task data.

Progress must be consistent with the application's defined status/progress logic. Do not introduce a new calculation rule without documenting it.

## 11. Task Management

The system must support:

- create task
- edit task
- view task
- assignment
- deadline
- priority
- status
- project association
- task-related comments/attachments/activity where permitted

Legacy DewaKoding terminology uses `Ticket`. PRISM uses `Task`. During migration, preserve data and relationships while transitioning terminology safely.

## 12. Collaboration

### Comments

Users can discuss project/task work through comments.

Comments should record author and timestamp.

### Attachments

Users can attach relevant files to supported project/task records.

### Activity History

Important project/task actions should be traceable through activity history.

Do not add an overly broad audit system unless needed by the existing implementation or explicit requirement.

## 13. Notification Management

PRISM includes notification functionality.

Notifications should be tied to relevant project/task events supported by the system.

DewaKoding already has email notifications and queue-based processing. Reuse this infrastructure where it matches PRISM requirements rather than rebuilding notification infrastructure.

## 14. Dashboard & Project Monitoring

Dashboard and monitoring provide authorized users with current project/task information.

Expected information includes:

- project name
- project manager
- project status
- start date
- deadline
- progress
- task count
- task status
- task deadline
- assignee
- priority

Required behavior:

- only show authorized projects/data
- reflect current project/task changes
- show overdue task information where applicable
- allow navigation to project detail
- handle no-data state
- handle access-denied state

## 15. Reporting Management

Reporting provides reports about:

- project
- project status
- project progress
- task
- task status
- deadlines

Reports may support:

- period filtering
- project filtering
- status filtering

Reports must:

- use PRISM database data
- respect role/access restrictions
- show a no-data message when no records match
- reflect current data when generated again

**Do not treat PDF/Excel export as a mandatory PRISM requirement unless separately approved.**

## 16. Google Calendar Integration

Purpose: connect selected PRISM project/task schedule or deadline information with Google Calendar.

Required:

- integration configuration
- Google Calendar API usage
- authorization
- send selected schedule/deadline
- show integration result
- error handling
- deployment-specific credentials
- enable/disable/update configuration

Rules:

- works only when configuration and credentials are available
- authorization is required
- credentials are configured for the organization's deployment
- Google service failure must not delete PRISM core data

## 17. Google Drive Integration

Purpose: support project-related file references through Google Drive.

Required:

- integration configuration
- Google Drive API usage
- authorization
- access authorized files
- link file to project/task
- show file references
- error handling
- deployment-specific credentials
- enable/disable/update configuration

Rules:

- works only when configuration/credentials are available
- authorization is required
- credentials are deployment-specific
- Google service failure must not delete PRISM core data

## 18. Explicit non-goals

Do not add these as core PRISM features without approval:

- payroll
- HR/recruitment
- accounting
- inventory
- procurement/ERP
- video conferencing
- advanced AI
- shared multi-organization SaaS
- public collaboration
- unapproved analytics
- unapproved export formats
- unapproved two-way Google synchronization

## 19. Quality requirements

Quality focus from the project plan:

- functional correctness
- authorization
- integration
- regression safety
- database consistency
- UI consistency
- usability
- deployment readiness

## 20. Compliance/security baseline

The project plan states:

- passwords are hashed using bcrypt
- authentication is described as JWT-based
- authorization uses RBAC
- each organization has a separate database/deployment
- UAT/demo data is for academic project purposes unless permission is obtained
- Google integrations follow Google Cloud terms

Because the DewaKoding base uses Laravel/Filament authentication, any change to JWT/session architecture must be treated as an explicit architecture decision rather than assumed during feature work.
