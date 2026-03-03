# Contributing to LaraPost

Thanks for contributing.

## Contribution Model

LaraPost accepts community changes through **forks + pull requests**.

- Do not push contribution branches directly to `prateekbhujel/larapost`
- Fork the repository to your own GitHub account
- Open pull requests from your fork branch into `main`

## Ground Rules

- Keep changes focused and small
- Add or update tests for behavior changes
- Avoid breaking behavior without documenting it in the PR and release notes
- Do not commit secrets or API tokens

## Local Setup (Fork Workflow)

```bash
# 1) Fork on GitHub, then clone your fork
git clone https://github.com/<your-username>/larapost.git
cd larapost

# 2) Add upstream remote
git remote add upstream https://github.com/prateekbhujel/larapost.git

# 3) Install and test
composer install
composer test
```

## Branching

Create branches from latest upstream `main` with clear names, for example:

- `feature/oauth-state-validation`
- `fix/linkedin-image-upload-error`
- `docs/release-checklist`

## Keep Your Fork in Sync

```bash
git fetch upstream
git checkout main
git rebase upstream/main
git push origin main
```

## Pull Request Checklist

- [ ] PR opened from a fork branch
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
