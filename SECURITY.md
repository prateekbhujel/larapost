# Security Policy

## Supported Versions

| Release line | Status |
| --- | --- |
| `2.x` | Active fixes and security updates |
| `1.x` | Critical security and compatibility backports only |

## Reporting a Vulnerability

Please report vulnerabilities privately by email:

- `prateekbhujelpb@gmail.com`

Include:

- affected version
- reproduction steps or proof of concept
- potential impact
- any suggested mitigation

Do not open public GitHub issues for unpatched vulnerabilities.

## High-Risk Surfaces

LaraPost handles provider credentials and can publish externally. Treat the following as privileged:

- the operator dashboard
- OAuth callback state and token exchange
- queue workers and scheduled publishing
- the optional MCP server
- any custom driver that receives account credentials

MCP is disabled by default and must never be exposed without authentication.

## Response Targets

- Initial response: within 72 hours
- Triage/update: within 7 days
- Patch release target: as quickly as practical based on severity
