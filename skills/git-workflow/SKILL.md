---
name: git-workflow
description: Use when committing, merging, rebasing, or cutting a release on a Git repository. Enforces single-purpose commits, a structured subject + body message format (capitalised imperative subject ≤ 72 chars, no trailing period, descriptive body), rebase-over-merge to keep feature branches current, and a `develop` → `master` release-merge convention with a `Release …` subject and a body that groups the included commits by category.
---

# Git workflow

## 1. One commit, one purpose

Each commit does exactly one thing. SOLID applies to history too — separation of concerns at the commit level is what makes diffs reviewable, blames meaningful, and reverts safe.

- Don't combine a bugfix with a feature.
- Don't sneak unrelated refactors into a feature commit.
- If a single change spans several concerns, split it (`git add -p`, multiple commits).
- Prefer many small, focused commits over one large grab-bag.

```
Good:  Add user export endpoint
       Fix off-by-one in invoice paginator

Bad:   Add user export and fix invoice paginator
```

## 2. Commit message format

Subject, blank line, body.

### Subject

- Capitalised first letter.
- Single line.
- No trailing period.
- ≥ 10 and ≤ 72 characters.
- Imperative mood — describe what the commit *does*, as if completing the sentence "If applied, this commit will …".

```
Good:  Add ULID column to users table
       Fix race condition in queue worker
       Refactor invoice service into actions

Bad:   added ulid column.                 # lowercase, past tense, trailing period
       wip                                # too short, not a description
       Update                             # tells you nothing
       Refactored the entire invoice service into a set of single-purpose actions and updated all callers   # too long
```

### Body

- Short description of *what* the commit does and *why* it's needed.
- Wrap around 72 columns.
- A few sentences or bullets — not an essay.
- Omit only when the subject genuinely says everything (rare).

```
Add ULID column to users table

Existing integer ids are leaking through API responses. Adding a ULID
column lets the model layer expose a stable, opaque identifier without
breaking internal foreign keys.
```

## 3. Stay current via rebase, not merge

When a feature branch falls behind its upstream, rebase onto it. Never resolve "behind" by merging the upstream back in — that produces a noisy merge commit and tangles history.

```
git fetch origin
git rebase origin/develop
```

Resolve any conflicts during the rebase, then continue (`git rebase --continue`).

Rule of thumb:

- Feature branches are **rebased** onto their upstream.
- Shared long-lived branches (`develop`, `master`) are **never rebased**.

## 4. Branch model

- `master` — released code only. Updated through release merges from `develop`.
- `develop` — integration branch. Day-to-day work lands here, typically through short-lived feature branches.
- Feature branches — branch off `develop`, rebase onto `develop` to stay current, and return to `develop` once ready (fast-forward after rebase, or a normal merge).

## 5. Release merges (`develop` → `master`)

This is the one place a merge commit is intentional. Use `--no-ff` so the merge commit is preserved as the release marker.

```
git checkout master
git pull --ff-only
git merge --no-ff develop
```

### Subject

Clearly identify the merge as a release. Recommended prefix: `Release`, followed by whatever identifier the project uses — version, date, codename. The exact suffix is not prescribed; consistency within a project is what matters.

```
Release v1.4.0
Release 2026-05-05
Release Aurora
```

### Body

A categorised summary of every commit included in the release. Group by category so a reader can scan what shipped. Use the categories that apply and skip the rest — extend the list when something genuinely doesn't fit.

Suggested categories: **Features**, **Bugfixes**, **Refactor**, **Performance**, **Docs**, **Tests**, **Chore**.

```
Release v1.4.0

Features:
- Add user export endpoint
- Add ULID column to users table

Bugfixes:
- Fix off-by-one in invoice paginator
- Fix race condition in queue worker

Refactor:
- Extract invoice service into actions

Chore:
- Bump Larastan to 2.9
```

## 6. Always propose the commit — never commit unprompted

Committing is never automatic. Even when the user asks for "a commit", an agent must:

1. Stage the intended changes (`git add -p` or explicit paths — avoid blanket `git add -A`).
2. Show the proposed subject and body to the user.
3. Wait for explicit approval before running `git commit`.

The same rule applies to merges, rebases, and release merges: state the plan, show the proposed message, and wait for a green light. If the user pushes back on the message or scope, revise and re-propose — do not commit and "fix it later" with an amend.

## 7. Things to avoid

- `--no-verify` — never bypass commit hooks.
- Force-pushing to shared branches (`develop`, `master`).
- One-word or "WIP" / "fix typo" subjects on commits intended for review.
- Merging the upstream into a feature branch instead of rebasing.
- Squashing unrelated commits together just to reduce the commit count — that defeats the one-purpose rule.
