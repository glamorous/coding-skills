# coding-skills

Personal Laravel/PHP coding conventions packaged as reusable [Claude Code](https://claude.com/claude-code) skills. Also compatible with the [agentskills.io](https://agentskills.io) `SKILL.md` spec, so the same files work in any agent that follows it (Cursor, GitHub Copilot, etc.).

## Install in Claude Code

Add this repo as a plugin marketplace, then install the plugin:

```
/plugin marketplace add glamorous/coding-skills
/plugin install coding-skills@coding-skills
```

`/plugin list` should show `coding-skills` enabled. Skills auto-activate when their `description` matches the current task — no manual invocation needed.

To update later: `/plugin marketplace update coding-skills` (or pin a tagged release; see [Updating](#updating)).

## Install in other AI agents (Cursor, Copilot, Codex, …)

Each skill is a folder under `skills/` containing a `SKILL.md` (YAML frontmatter `name` + `description`) and an optional `resources/` folder with copy-able artefacts (traits, validation rules, configs). The format follows the [agentskills.io](https://agentskills.io) `SKILL.md` spec, so any compliant agent can consume them.

**Via the agentskills CLI** (recommended — keeps the skill folder in sync):

```
npx skills add glamorous/coding-skills
```

This drops the `skills/` tree into the agent's skills directory (`.cursor/skills/`, `.github/skills/`, `~/.codex/skills/`, etc. depending on the agent). Re-run to pull updates.

**Manually**: clone or download this repo and either point your agent's skills path at `skills/`, or copy individual skill folders (e.g. just `skills/php-style/`) into your agent's skills directory.

Either way, the agent picks skills up by matching the `description` field against the active task, the same way Claude Code does.

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

Each skill explains its rules and (where relevant) ships supporting code under its own `resources/` folder. The SKILL.md tells you which file to copy where.

## Updating

Bump the `version` field in `.claude-plugin/plugin.json` for tagged releases. Without a version bump, Claude Code uses the commit SHA — every push is effectively a new version for users running `/plugin update`.

## License

MIT.
