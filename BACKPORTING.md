# Backporting Policy

LaraPost keeps only `main` as a standing development branch.

Release tags are the source of truth for older versions. A maintenance branch is created from the relevant release tag only when a real backport is needed, and it can be removed again after the patch release.

## Active development

- `main`: current development and the active 2.x line.
- short-lived feature, fix, and release branches: deleted after merge.
- old major branches: created on demand, not kept permanently.

## Backport criteria

A fix is considered for an older major only when all of the following are true:

- it fixes a real bug or security issue present in that release line
- it can be applied without changing the documented public contract
- it has regression coverage where practical
- it does not pull new architecture or features into the maintenance line

New providers, MCP functionality, new queue architecture, and new public APIs are not backported.

## Workflow

1. Fix and test the issue on `main` when it still applies there.
2. Create the maintenance branch from the latest affected release tag, for example `git switch -c 1.x v1.0.0`.
3. Cherry-pick or reimplement the minimal fix.
4. Run the older line's supported CI matrix.
5. Tag the patch release.
6. Remove the maintenance branch when it no longer has active work.

This keeps the repository branch list small without giving up disciplined backports.
