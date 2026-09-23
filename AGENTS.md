# LaraPost coding agent guide

This repository is LaraPost 2.x. Use this file as the default working agreement for coding agents.

## Runtime contract

- PHP 8.3+
- Laravel 12 or 13
- Built-in platforms: Facebook, Twitter / X, LinkedIn, TikTok
- TikTok defaults to `upload`; Direct Post is explicit opt-in
- Operator routes are authenticated by default
- MCP is optional and requires `laravel/mcp`

## Source of truth

Start with these files before making assumptions:

- `config/larapost.php` — configuration, environment variables, enabled platforms, queue, scheduler, routes, MCP
- `src/PostBuilder.php` — public fluent publishing API
- `src/SocialMediaManager.php` — driver resolution, publish flow, token refresh
- `src/Drivers/` — provider-specific behavior
- `src/Jobs/` — queued and scheduled execution
- `src/Console/Commands/DoctorCommand.php` — production readiness checks
- `routes/web.php` — operator and OAuth routes
- `src/Mcp/` — optional AI/MCP surface
- `docs/agents/index.html` and `docs/llms.txt` — agent-facing documentation

## Development checks

Run at minimum:

```bash
composer validate --strict --no-check-lock
composer test
```

CI currently exercises:

- PHP 8.3 / Laravel 12
- PHP 8.4 / Laravel 12
- PHP 8.4 / Laravel 13
- PHP 8.5 / Laravel 13
- Composer security audit

## Change rules

- Do not weaken operator authentication or OAuth state validation.
- Do not silently enable platforms or provider behavior the application did not configure.
- Do not add implicit TikTok privacy/consent choices. Direct Post requires explicit creator-facing decisions.
- Do not expose credentials or provider tokens through MCP or read APIs.
- Preserve persisted-before-dispatch behavior for queued/scheduled posts.
- Add regression coverage for bug fixes and provider behavior changes.
- Keep provider-specific behavior inside drivers when practical.
- Update docs whenever commands, environment variables, supported capabilities, or public APIs change.
- Do not alter release tags. Historical maintenance branches should be created from release tags only when a real backport is needed.

## Documentation style

Documentation should be usable by both developers and coding agents:

- stable headings and anchors
- copy-pasteable examples
- explicit prerequisites and operational requirements
- clear supported/unsupported capability statements
- no hidden assumptions about provider setup
- links to the exact next page for multi-step workflows

The public docs live in `docs/` and deploy through GitHub Pages.
