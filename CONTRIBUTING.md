# Contributing to LaraPost

Thanks for contributing.

## Which branch?

New features, provider work, architecture changes, and bug fixes that affect the current release line target `main`.

The `1.x` branch is only for critical backports. See [BACKPORTING.md](./BACKPORTING.md).

## Contribution model

Use a fork and pull request:

```bash
git clone https://github.com/<your-username>/larapost.git
cd larapost
git remote add upstream https://github.com/prateekbhujel/larapost.git
composer install
composer test
```

## Expectations

- Solve one concrete problem per pull request.
- Add regression coverage for behavior fixes.
- Keep provider behavior aligned with current provider documentation.
- Do not weaken OAuth state checks, route authorization, or MCP authentication to make a test pass.
- Never commit API tokens, access tokens, client secrets, or production account data.
- Update public docs when behavior, configuration, or supported platforms change.
- Treat unrelated CI failures separately rather than changing production code to satisfy them.

## Provider changes

Provider APIs change frequently. A provider pull request should explain:

- the provider API/version involved
- required scopes/permissions
- media limitations
- authentication flow
- expected error handling
- how the behavior was tested

## MCP changes

MCP tools that mutate data or publish externally must be marked as side-effecting and must not return provider credentials.

Read tools should expose the minimum data an AI client needs.

## Pull request checklist

- [ ] Tests pass locally
- [ ] New behavior has regression coverage
- [ ] Public API/config impact is documented
- [ ] Security implications were considered
- [ ] Provider restrictions are described accurately
- [ ] Changelog entry added for notable user-facing changes
