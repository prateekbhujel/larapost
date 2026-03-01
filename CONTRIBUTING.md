# Contributing to LaraPost

Thanks for contributing.

## Ground Rules

- Keep changes focused and small
- Add or update tests for behavior changes
- Avoid breaking behavior without documenting it in the PR and release notes
- Do not commit secrets or API tokens

## Local Setup

```bash
git clone https://github.com/prateekbhujel/larapost.git
cd larapost
composer install
composer test
```

## Branching

Use clear branch names, for example:

- `feature/oauth-state-validation`
- `fix/linkedin-image-upload-error`
- `docs/release-checklist`

## Pull Request Checklist

- [ ] Tests pass locally (`composer test`)
- [ ] Public API/config impact is documented
- [ ] README or docs updated when behavior changed
- [ ] Changelog entry added for notable user-facing changes

## Commit Messages

Use concise conventional-style commits where possible:

- `feat: add queued retry backoff override`
- `fix: prevent duplicate scheduled post claims`
- `docs: clarify production cron setup`

## Reporting Issues

Use GitHub Issues and include:

- package version
- Laravel version
- PHP version
- exact error message
- minimal reproduction steps
