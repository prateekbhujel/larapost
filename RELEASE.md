# Release Playbook

LaraPost follows semantic versioning.

## Branch model

- `main` is the only standing development branch.
- Short-lived feature and release-preparation branches are deleted after merge.
- Maintenance branches such as `1.x` are created from release tags only when a real backport is needed.

See [BACKPORTING.md](./BACKPORTING.md).

## Before tagging

1. Run the full CI matrix.
2. Run `composer validate --strict`.
3. Run `composer audit`.
4. Run the package test suite.
5. Smoke test installation in a fresh supported Laravel application.
6. Run `php artisan larapost:doctor`.
7. Verify dashboard access is protected.
8. If MCP changed, test authenticated read and write tools with an MCP client.
9. If provider code changed, test against provider development credentials when available.
10. Update `CHANGELOG.md`, README, docs, and `UPGRADE.md` when applicable.

## Stable release

Tag the exact green `main` commit and create the GitHub release from that tag.

```bash
git checkout main
git pull --ff-only
git tag vX.Y.Z
git push origin vX.Y.Z
```

The v2.0.0 preparation includes a guarded release workflow that creates the v2.0.0 tag only after the final `main` CI run succeeds.

## Packagist

Confirm the new tag appears on Packagist and that a fresh Composer install resolves it.

## Post-release

- verify GitHub Pages deployment
- verify Packagist metadata and requirements
- install the tagged package in a clean supported Laravel app
- delete merged preparation branches
