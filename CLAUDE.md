# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

A collection of personal coding-related skills following the [agentskills.io](https://agentskills.io) `SKILL.md` spec — mostly Laravel/PHP conventions, plus a handful of workflow/runbook skills (e.g. Sentry triage, Git workflow). There is no application code, no build step, no test suite — the deliverables are `SKILL.md` files (plus optional `assets/` and `references/` artefacts) that any compliant agent (Claude Code, Cursor, Copilot, Codex, …) loads and follows.

Two flavours of skill live here:

- **Convention skills** auto-activate when the agent edits matching code (e.g. `laravel-models` triggers on Eloquent model edits).
- **Workflow skills** are invoked by the user — by phrasing ("triage sentry issues") or by `/`-name in agents that support it (`/sentry-triage`).

Don't add agent-specific assumptions to a `SKILL.md` body — keep it portable across consumers. Workflow skills that depend on a specific MCP server should say so explicitly and fail fast in a preflight rather than silently degrade.

## Repository layout

- `skills/<skill-name>/SKILL.md` — one skill per folder. Frontmatter requires `name` (must match folder) and `description`.
- `skills/<skill-name>/assets/` — copy-able artefacts (traits, filters, rules, GrumPHP/PHPStan/Pint configs) referenced by the skill body. Paths inside `assets/` mirror where they belong in a target Laravel app (e.g. `assets/Traits/HasIdentifier.php` → `app/Traits/HasIdentifier.php`).
- `skills/<skill-name>/references/` — markdown documentation that the skill body instructs the agent to load on demand (e.g. `references/form-requests.md`). Use this for content that is too long to inline in `SKILL.md` but should be loaded when a specific trigger fires.

This split follows the agentskills.io spec — `assets/` for files the agent copies into the target project, `references/` for files the agent reads to learn more.

## Authoring rules for SKILL.md

The `description` is the activation signal — agents match it against the user's current task. It must:

1. Start with **"Use when …"** and enumerate the concrete triggers (file types, class kinds, operations).
2. Then summarise **what the skill enforces**, so the agent knows what's inside before loading the body.

Both halves matter — triggers alone get the skill loaded but the agent doesn't know the rules; rules alone never get the skill loaded.

Skill bodies are numbered sections (`## 1. …`, `## 2. …`) of terse rules with short code examples. Match this style when adding or editing skills — long prose dilutes the signal an agent picks up.

## Cross-skill conventions encoded here

These show up in multiple skills and inform any work that touches Laravel projects via these skills:

- **Identifiers leave the DB layer as ULIDs, never `id`.** The `laravel-models` skill is the source of truth; `laravel-routing`, `laravel-resources`, and `laravel-architecture` all rely on this and reference it.
- **GrumPHP is the quality gate.** `code-quality-grumphp` defines what runs on commit (PHPStan level 8, Pint, complexity, magic-number ban, full test suite, structured commit message). Critical meta-rule: **propose fixes when a check fails — never auto-fix or `--no-verify`.**
- **`collect()` over native array functions, `Arr::get()` over bracket access** — uniform, no exceptions (`laravel-collections`).
- **Strict types + strict comparison everywhere** (`php-style`).

When editing one skill, scan the others for cross-references — the rules are intentionally interlocking.

## Common tasks

- **Add a skill**: create `skills/<name>/`, write `SKILL.md` with the frontmatter pattern above, add it to the table in `README.md`.
- **Test locally**: copy the skill folder into `~/.claude/skills/<name>/` (or run `npx skills add <local-path-or-repo>` once the change is pushed), then trigger a matching task in any agent to verify the `description` activates the skill.

## Contributor setup

After cloning, activate the repo's pre-commit hook once:

```
git config core.hooksPath .githooks
```

The hook validates every staged `skills/*/SKILL.md`: frontmatter must parse as YAML, `name` must match the folder, `description` must be present and start with `Use `. It only needs Ruby, which ships with macOS and most Linux distros. It runs on `git commit` and refuses the commit if a check fails — fix the file and re-stage; do not bypass with `--no-verify`.

Common YAML pitfall in `description`: a colon followed by a space (`cycle: fetch`) inside an unquoted plain scalar is parsed as a nested mapping and breaks the file. Replace with a dash (`cycle — fetch`) or rephrase. The hook catches this.

## Committing changes to this repo

The `git-workflow` skill defines the general commit rules (single-purpose commits, capitalised imperative subject ≤ 72 chars, descriptive body, propose-and-await-approval). Consumers pick up changes by re-running `npx skills add …`, so no version manifest needs bumping — the git SHA is the version.
