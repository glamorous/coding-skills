# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

A **Claude Code plugin** that ships personal Laravel/PHP coding conventions as reusable skills. There is no application code, no build step, no test suite — the deliverables are `SKILL.md` files (plus optional `resources/` artefacts) that other agents load and follow when editing Laravel/PHP projects.

The same files are also `agentskills.io`-compatible, so they're consumed by Cursor, Copilot, etc. Don't add Claude-Code-only assumptions to a `SKILL.md` body.

## Repository layout

- `.claude-plugin/plugin.json` — plugin manifest. Bump `version` for tagged releases; without a bump, `/plugin update` keys off the commit SHA.
- `skills/<skill-name>/SKILL.md` — one skill per folder. Frontmatter requires `name` (must match folder) and `description`.
- `skills/<skill-name>/resources/` — copy-able artefacts (traits, filters, rules, GrumPHP/PHPStan/Pint configs) referenced by the skill body. Paths inside resources mirror where they belong in a target Laravel app (e.g. `resources/Traits/HasIdentifier.php` → `app/Traits/HasIdentifier.php`).

## Authoring rules for SKILL.md

The `description` is the activation signal — agents match it against the user's current task. It must:

1. Start with **"Use when …"** and enumerate the concrete triggers (file types, class kinds, operations).
2. Then summarise **what the skill enforces**, so the agent knows what's inside before loading the body.

Both halves matter — triggers alone get the skill loaded but the agent doesn't know the rules; rules alone never get the skill loaded.

Skill bodies are numbered sections (`## 1. …`, `## 2. …`) of terse rules with short code examples. Match this style when adding or editing skills — long prose dilutes the signal an agent picks up.

## Cross-skill conventions encoded here

These show up in multiple skills and inform any work that touches Laravel projects via this plugin:

- **Identifiers leave the DB layer as ULIDs, never `id`.** The `laravel-models` skill is the source of truth; `laravel-routing`, `laravel-resources`, and `laravel-architecture` all rely on this and reference it.
- **GrumPHP is the quality gate.** `code-quality-grumphp` defines what runs on commit (PHPStan level 8, Pint, complexity, magic-number ban, full test suite, structured commit message). Critical meta-rule: **propose fixes when a check fails — never auto-fix or `--no-verify`.**
- **`collect()` over native array functions, `Arr::get()` over bracket access** — uniform, no exceptions (`laravel-collections`).
- **Strict types + strict comparison everywhere** (`php-style`).

When editing one skill, scan the others for cross-references — the rules are intentionally interlocking.

## Common tasks

- **Add a skill**: create `skills/<name>/`, write `SKILL.md` with the frontmatter pattern above, add it to the table in `README.md`.
- **Tag a release**: bump `.claude-plugin/plugin.json` `version`, commit, tag.
- **Test locally**: install via `/plugin install <local-path-or-repo-url>` in Claude Code, then `/plugin list` to confirm and trigger a matching task to verify activation.

## Committing changes to this repo

The `git-workflow` skill defines the general commit rules (single-purpose commits, capitalised imperative subject ≤ 72 chars, descriptive body, propose-and-await-approval). On top of that, **every commit to this repo must bump `version` in `.claude-plugin/plugin.json`** as part of the same commit, so `/plugin update` consumers pick up the change deterministically (without a bump, Claude Code falls back to the commit SHA).

Bump the version following semver, sized to what changed:

- **Major** (`X.0.0`) — breaking change for consumers: removing or renaming an existing skill, deleting a `resources/` file someone may already be copying, or altering a frontmatter `name` (which is the skill identifier).
- **Minor** (`0.X.0`) — additive change: a new skill folder, new `resources/` artefact, or a substantial rewrite of an existing skill that meaningfully expands what it covers.
- **Patch** (`0.0.X`) — small adjustments: tightening a rule, fixing a typo, trimming a description, clarifying an example, README tweaks, CLAUDE.md edits.

The version bump is part of the same commit as the change it describes — never a separate "bump version" commit.
