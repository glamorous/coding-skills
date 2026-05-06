# coding-skills

Personal coding-related skills packaged following the [agentskills.io](https://agentskills.io) `SKILL.md` spec, so they work in any compliant agent (Claude Code, Cursor, GitHub Copilot, Codex, …). Mostly Laravel/PHP conventions, plus a few workflow/runbook skills.

## Install

**Via the agentskills CLI** (recommended):

```
# Install everything
npx skills add glamorous/coding-skills

# Or cherry-pick individual skills
npx skills add glamorous/coding-skills --skill laravel-models --skill php-style
```

This drops the selected skills into the agent's skills directory — `~/.claude/skills/` for Claude Code, `.cursor/skills/`, `.github/skills/`, `~/.codex/skills/`, etc. depending on the agent. Re-run to pull updates.

**Manually**: clone or download this repo and either point your agent's skills path at `skills/`, or copy individual skill folders (e.g. just `skills/php-style/`) into your agent's skills directory.

Skills auto-activate when their `description` matches the active task — no manual invocation needed.

## Skills

| Skill | When it activates |
|---|---|
| `laravel-architecture` | Controllers, Form Requests, Actions, API Resources, global helpers used across the request lifecycle |
| `laravel-models` | Eloquent models, custom Builders, Filters, migrations, and anywhere a model reference is exposed to the outside |
| `laravel-routing` | Route definitions and URL generation |
| `laravel-data-objects-enums` | PHP enums and Data Object (DTO) classes |
| `laravel-resources` | API Resource classes (`JsonResource`) |
| `php-style` | Any PHP code — strict types, comparison, type hints, `use` statements, array shapes, constants, comments, formatting |
| `laravel-collections` | Array transformations and associative-array reads |
| `code-quality-grumphp` | Projects that already have GrumPHP configured, or explicit requests to set it up |
| `git-workflow` | Committing, merging, rebasing, or cutting a release |
| `sentry-triage` | Walking through unresolved Sentry issues — fetch, root-cause, fix, commit (requires Sentry MCP) |

Each skill is a folder under `skills/<name>/` containing a `SKILL.md` (the rules) and, where relevant, an `assets/` folder with copy-able artefacts (traits, validation rules, configs) and a `references/` folder with markdown the skill loads on demand. The SKILL.md tells you which file to copy where.

## License

MIT.
