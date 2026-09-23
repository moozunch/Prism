# AI Coding Workflow — Token-Efficient PRISM Development

## Goal

Use AI heavily without repeatedly sending the entire PRISM specification or entire codebase into every prompt.

## 1. Keep permanent context small

The AI should always have:

- `AGENTS.md`

It should read additional files only when needed.

Recommended context map:

| Task | Read |
|---|---|
| Small bug | `AGENTS.md` + relevant source |
| Organization | `AGENTS.md` + `PRISM_REQUIREMENTS.md` + `ARCHITECTURE.md` |
| Task migration | `AGENTS.md` + `PRISM_REQUIREMENTS.md` + `DEWAKODING_BASELINE.md` + `MIGRATION_PLAN.md` |
| Google Calendar | `AGENTS.md` + relevant PRISM requirements + architecture |
| Reporting | `AGENTS.md` + reporting requirements |
| Deployment | `AGENTS.md` + architecture + migration plan |

Do not paste the full SRS into every chat.

## 2. Ask for small coherent changes

Good:

> Implement P1-05 Division Management from docs/PRISM_REQUIREMENTS.md. First inspect existing User/role structures and relevant Filament resources. Do not modify Project/Task yet. Implement migration, model, resource, authorization, and focused tests. Report files changed and tests run.

Bad:

> Convert this entire repository into PRISM.

The second prompt causes unnecessary broad exploration and context usage.

## 3. Ask the agent to inspect before coding

Use:

> Inspect first. Do not change code yet. Identify the files, models, migrations, policies, and resources affected by P3-05 Ticket → Task. Return a short migration plan.

Then approve/continue.

## 4. Use task IDs

Always reference `TASK_BACKLOG.md`.

Example:

> Work only on P3-06. Do not start P3-07 or P4 work.

This prevents scope creep.

## 5. Do not paste files unnecessarily

If the coding agent has filesystem access, tell it to inspect the repository.

Prefer:

> Read `app/Models/...` and the related migration.

Instead of pasting a 500-line file into chat.

## 6. Keep a compact progress file

Create/update:

`docs/PROGRESS.md`

Suggested format:

```md
# Current Progress

## Current task
P1-05 Division Management

## Completed
- P0 baseline
- P1-01 branding

## Current changes
- ...

## Tests
- ...

## Known issues
- ...

## Next task
P1-06 Member Management
```

At the start of a new AI context, ask it to read `AGENTS.md` and `docs/PROGRESS.md`.

## 7. Commit after coherent milestones

Recommended commits:

- `chore: establish prism branding`
- `feat: add organization management`
- `feat: add division management`
- `feat: adapt member management`
- `feat: adapt project management`
- `feat: migrate ticket domain to task`
- `feat: add prism reporting`
- `feat: add google calendar integration`
- `feat: add google drive integration`

This makes rollback easier.

## 8. Token-saving principle

The main strategy is **context selection**, not a magic prompt.

Keep stable instructions in local files and ask the agent to read only relevant context.

For API-based workflows, prompt caching can reduce input token cost when stable prompt prefixes are reused. For ordinary coding-agent use, the bigger practical win is avoiding repeated full-repository/full-SRS dumps.

## 9. Context reset strategy

When a conversation becomes large:

1. update `docs/PROGRESS.md`
2. commit working changes
3. start a fresh AI context
4. tell the agent:
   - read `AGENTS.md`
   - read `docs/PROGRESS.md`
   - read the task-specific docs
   - inspect git diff/status
5. continue from the next task

This is often cleaner than carrying a huge chat history forever.

## 10. Never ask the model to explain chain-of-thought

Ask for concise implementation summaries, assumptions, changed files, and verification results.

Example:

> Give only: summary, files changed, tests run, unresolved issues.

## 11. Definition of done

A coding task is not complete merely because code was generated.

Require:

- implementation
- migration correctness
- authorization check
- validation
- focused test
- regression check when relevant
- concise summary

