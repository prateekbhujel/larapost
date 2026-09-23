# Backporting Policy

LaraPost keeps release branches only when they have a maintenance purpose.

## Branches

- `main`: current development and the active 2.x line.
- `1.x`: critical fixes for the original 1.x release line.

Feature work and breaking changes target `main`.

A fix is considered for `1.x` only when all of the following are true:

- it fixes a real bug or security issue present in 1.x
- the patch can be applied without changing the documented 1.x public contract
- the change has regression coverage
- the backport does not pull new 2.x architecture into the old line

New providers, MCP functionality, new queue architecture, and new public APIs are not backported.

## Workflow

1. Fix and test the issue on `main` when it still applies there.
2. Open or create a targeted backport for `1.x`.
3. Keep the backport minimal and include the same regression test where practical.
4. Release a patch from the affected stable line.

Do not keep stale feature branches as pseudo-release branches.
