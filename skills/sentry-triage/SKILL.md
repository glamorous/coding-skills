---
name: sentry-triage
description: Use when the user asks to triage, resolve, fix, or work through unresolved Sentry issues for the current project — typical phrasings are "fix sentry errors", "resolve sentry issues", "go through unresolved sentry", or invoking /sentry-triage. Requires a Sentry MCP server (tools prefixed `mcp__sentry__*`). Enforces a strict per-issue cycle — fetch full issue + stacktrace, identify the first-party file/line, do real root-cause analysis (not symptom patching), grep the entire codebase for the same vulnerable pattern, present root cause + proposed fix + every other affected location to the user, wait for explicit approval before any edit, then commit with a `Fixes <ISSUE-ID>` trailer so Sentry auto-resolves the issue.
---

# Sentry triage

Walks an agent through every unresolved Sentry issue in the current project, one
at a time. Each issue is investigated to root cause, the same pattern is hunted
across the whole codebase, the fix is proposed and approved, then committed with
a `Fixes <ID>` trailer so Sentry auto-closes it.

This is a workflow skill, not a coding-convention skill — it expects to be
*invoked* (via natural phrasing or `/sentry-triage`), not auto-applied to every
file edit.

## 1. Preflight — refuse to start if requirements are missing

Before doing anything else:

- **Sentry MCP must be available.** Verify that `mcp__sentry__search_issues`,
  `mcp__sentry__get_sentry_resource`, and `mcp__sentry__update_issue` are
  callable. If not, stop and point the user at `references/sentry-mcp-setup.md`.
  Do **not** try to fall back to scraping the Sentry UI or asking the user to
  paste issue dumps — the structured tool output is what the rest of the skill
  depends on.
- **Working directory must be a Git repo.** The `Fixes <ISSUE-ID>` trailer only
  auto-resolves through Git integration. If `.git` is missing, stop and tell
  the user.

A failed preflight ends the skill cleanly. Don't continue half-configured.

## 2. Fetch Sentry configuration

Look up `organizationSlug`, `projectSlugOrId`, and `regionUrl` in the project's
CLAUDE.md (or any other project doc). Recommended block to look for:

```
## Sentry
- organizationSlug: acme-co
- projectSlugOrId: web-app
- regionUrl: https://us.sentry.io
```

If any value is missing, ask the user. Never hardcode these in the skill.

Region URL examples: `https://us.sentry.io`, `https://de.sentry.io`,
`https://eu.sentry.io`. Match the user's Sentry org region.

## 3. Fetch unresolved issues

```
mcp__sentry__search_issues
  organizationSlug:    <from config>
  projectSlugOrId:     <from config>
  regionUrl:           <from config>
  naturalLanguageQuery: "unresolved issues"
  limit:               20
```

Present the result as a compact table — Issue ID, title, event count, last seen.
Confirm with the user that they want to walk through the full list before
starting the per-issue loop.

## 4. Per-issue cycle

For each issue, run these six steps in order. **Never** batch fixes across
issues — one issue, one approval, one commit.

### 4a. Investigate

- Pull full details with `mcp__sentry__get_sentry_resource`.
- Read the stacktrace top-down, but anchor on the first **first-party** frame
  (skip vendor / framework frames).
- Open the source file at the failing line. Read enough surrounding context to
  understand the call.
- Trace inputs: where does the failing variable come from, who calls this
  method, what's its expected shape.

### 4b. Root cause

Answer *why*, not just *where*. Common categories:

- Null / missing relation that's assumed to exist.
- Type mismatch (string vs int, model vs id).
- Race condition (record deleted between fetch and use).
- Missing eager load → N+1 that times out.
- Untrusted external data (webhook payload, API response, user input).

If the cause is "the data is wrong upstream", chase upstream — don't band-aid
downstream.

### 4c. Cross-codebase grep — non-negotiable

Before proposing any fix, search the whole codebase for the **same vulnerable
pattern**, not just the file Sentry pointed at.

Examples:

- If `$user->profile->name` crashed because `profile` was null → grep for every
  other `$user->profile->` access.
- If `Order::find($id)->total()` blew up on a deleted order → grep for
  `Order::find(` without a null check.
- If a Resource missed an eager load → trace every Resource/Controller pair
  that touches the same relation.

List every hit. The Sentry-reported one is rarely the only one.

### 4d. Propose fix — wait for approval

Present to the user, in this order:

1. **Root cause** — one paragraph, plain language.
2. **Proposed fix** — the exact diff for the reported location.
3. **Other affected locations** — full list of every file/line found in 4c that
   has the same vulnerability, with the same fix sketched per-site.
4. **Total** — number of files and lines that will change.

Then **stop**. Do not edit until the user explicitly approves. If the user
pushes back ("only fix the reported one", "use a different approach", "skip
this issue"), revise — don't argue and don't proceed.

### 4e. Implement and commit

After approval:

- Apply the edits.
- Run the project's quality gate if one exists (PHPStan, Pint, tests). If it
  fails, stop and fix — never `--no-verify`.
- Commit with a clear subject and a body that includes a `Fixes <ISSUE-ID>`
  trailer. Sentry auto-resolves the issue on push.

```
Fix null profile access on user export

Profile may be null for users imported before the profile-onboarding flow
shipped. Guarding the access path here and at the four other call sites
the same pattern was used.

Fixes ACME-WEB-12K
```

`Fixes <ID>` in the commit body is sufficient. Calling
`mcp__sentry__update_issue` on top of a `Fixes` commit is redundant — pick one
path, don't do both.

### 4f. Next issue

Move to the next unresolved issue in the list and start again at 4a. Don't
skip ahead, don't combine fixes.

## 5. Final report

After the list is exhausted (or the user calls a stop), summarise:

| Issue ID | Error type | Fix applied | Status |
|----------|------------|-------------|--------|
| …        | …          | …           | resolved / skipped / deferred |

Note any issues that were skipped and *why* — "needs product decision",
"reproducer unclear", "out of scope". A skipped issue with no reason is a bug
in this workflow.

## 6. Meta-rules

- **Never apply superficial fixes.** Understand root cause first.
- **Patterns over instances.** One reported bug usually implies several
  unreported siblings — check them.
- **Use database / schema context.** Don't guess at column nullability or
  relation cardinality; look it up.
- **Reuse existing patterns.** If similar problems are solved elsewhere in the
  codebase, copy that style instead of inventing a new one.
- **One issue at a time.** Always wait for approval per issue.
- **N+1 issues need a full path trace.** Walk Controller → Resource →
  relations to find every missing eager load — not just the one Sentry showed.
