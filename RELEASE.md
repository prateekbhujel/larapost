# Release Playbook

LaraPost follows semantic versioning.

## Branch model

- `main` is the active release line and current development branch.
- `1.x` exists only for critical backports to the original release line.
- Short-lived feature/release preparation branches should be deleted after merge.

## Before tagging

1. Run the full CI matrix.
2. Run `composer validate --strict`.
3. Run `composer audit`.
4. Run the package test suite.
5. Smoke test installation in a fresh supported Laravel application.
6. Run `php artisan larapost:doctor`.
7. Verify dashboard access is protected.
8. If MCP changed, test read and write tools against an MCP client with authentication.
9. If provider code changed, test against provider sandbox/development credentials when available.
10. Update `CHANGELOG.md`, README, docs, and `UPGRADE.md` when applicable.

## Tagging

```bash
git tag vX.Y.Z
git push origin vX.Y.Z
```

Create the GitHub release from the tag and use the changelog entry as the basis for release notes.

## Packagist

Confirm the new tag appears on Packagist and that a fresh Composer install resolves it.

## Post-release

- verify the docs deployment
- verify the Packagist metadata and requirements
- verify installation from a clean Laravel app
- delete the merged preparation branch
