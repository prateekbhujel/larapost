# Release Playbook

Use this checklist for each stable release.

## 1. Prepare

- Ensure tests pass:

```bash
composer test
```

- Update docs and `CHANGELOG.md`
- Confirm `composer.json` package metadata and links are current

## 2. Create release commit

```bash
git add .
git commit -m "chore(release): prepare vX.Y.Z"
```

## 3. Tag

```bash
git tag vX.Y.Z
git push origin HEAD
git push origin vX.Y.Z
```

## 4. Publish notes

- Create GitHub Release from the tag
- Copy key items from `CHANGELOG.md`

## 5. Packagist sync

- If webhook is configured, Packagist will auto-update
- Otherwise trigger update manually from the Packagist package page

## 6. Post-release checks

- Verify `composer show prateekbhujel/laravel-social-sync --all` resolves new tag
- Smoke test install in a fresh Laravel app
