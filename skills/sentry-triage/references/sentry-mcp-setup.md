# Sentry MCP setup

The `sentry-triage` skill requires a Sentry MCP server to be available to the
agent. If the preflight in section 1 of `SKILL.md` failed, follow these steps.

## What you need

- A Sentry account with at least **read** access to the project you want to
  triage, and **resolve** permission if you want the skill to call
  `mcp__sentry__update_issue`.
- A Sentry auth token (User Auth Token or Internal Integration token) with the
  scopes `event:read`, `project:read`, and `event:write`.
- An MCP-capable agent (Claude Code, Cursor, or any client that speaks the
  Model Context Protocol).

## Install

Sentry maintains the official MCP server. Follow the current setup guide at:

<https://docs.sentry.io/product/sentry-mcp/>

The docs cover both the hosted endpoint and self-hosted options, and they stay
in sync with the tool names this skill expects (`mcp__sentry__search_issues`,
`mcp__sentry__get_sentry_resource`, `mcp__sentry__update_issue`, …).

## Verify

After installing, restart the agent and re-run the skill. The preflight should
now pass — the agent will list `mcp__sentry__*` tools as available.

If the tools still don't appear:

- Check the agent's MCP config file (`~/.claude.json` for Claude Code, or the
  equivalent for your agent) and make sure the Sentry server is listed and
  enabled.
- Check the agent's logs for connection errors — usually a missing token or a
  wrong region URL.

## Configuration block for `CLAUDE.md`

Once the MCP works, add a small block to the project's `CLAUDE.md` so the skill
finds the org / project / region without asking:

```
## Sentry
- organizationSlug: <your-org>
- projectSlugOrId: <your-project>
- regionUrl: https://<region>.sentry.io
```

Region is `us`, `eu`, or `de` for the hosted Sentry; for self-hosted, use the
full base URL.
