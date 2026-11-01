# Documentation Index

Technical knowledge base for the Mall application. Start with the root [AGENTS.md](../AGENTS.md), then read in this order:

1. [PROJECT_OVERVIEW.md](PROJECT_OVERVIEW.md)
2. [ARCHITECTURE.md](ARCHITECTURE.md)
3. [PROJECT_STATUS.md](PROJECT_STATUS.md)
4. The document for the area you are changing.

Documentation describes **implemented** behaviour. Anything planned is explicitly labelled *Planned* or *Proposed*. If docs and code disagree, the code wins until the discrepancy is resolved (see AGENTS.md).

## Existing documents

| Document | Contents |
|---|---|
| [PROJECT_OVERVIEW.md](PROJECT_OVERVIEW.md) | Purpose, scope, roles, stack, status, limitations |
| [PROJECT_STATUS.md](PROJECT_STATUS.md) | What is done / in progress / next |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Layers, request flow, patterns |
| [DEVELOPMENT_GUIDE.md](DEVELOPMENT_GUIDE.md) | Local setup and daily workflow |
| [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) | Every table, column, index |
| [DATABASE_RELATIONSHIPS.md](DATABASE_RELATIONSHIPS.md) | Relationships and ER diagram |
| [SERVICE_LAYER.md](SERVICE_LAYER.md) | Service conventions and catalogue |
| [AUTHENTICATION_AND_AUTHORIZATION.md](AUTHENTICATION_AND_AUTHORIZATION.md) | Sign-in, password reset, roles, policies |
| [BUSINESS_RULES.md](BUSINESS_RULES.md) | Enforced business rules and where |
| [LIVEWIRE_COMPONENTS.md](LIVEWIRE_COMPONENTS.md) | Every component, layout and form object |
| [UUID_STRATEGY.md](UUID_STRATEGY.md) | UUID primary key rules |
| [SECURITY.md](SECURITY.md) | Security controls, assumptions, gaps |
| [TESTING_GUIDE.md](TESTING_GUIDE.md) | How to run and write tests |
| [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) | Requirements and deployment procedure |
| [BACKUP_AND_RECOVERY.md](BACKUP_AND_RECOVERY.md) | Backing up the database and secrets |
| [TROUBLESHOOTING.md](TROUBLESHOOTING.md) | Known problems and fixes |
| [DECISIONS.md](DECISIONS.md) | Architecture decision records |
| [CHANGELOG.md](CHANGELOG.md) | Chronological change history |

## Documents to create when their feature is implemented

These are required by `prompt.md` but intentionally do not exist yet, because there is no implemented behaviour to describe. Create each one in the same change that introduces the first relevant code.

| Document | Create when… |
|---|---|
| FINANCIAL_LOGIC.md | the first money calculation is implemented (also add financial rules to BUSINESS_RULES.md) |
| SALES_MANAGEMENT.md, modules/sales.md | sales are implemented |
| PAYMENT_SYSTEM.md, modules/payments.md | payments are implemented |
| PROMOTIONS_SYSTEM.md, modules/promotions.md | promotions are implemented |
| REFUNDS_AND_CANCELLATIONS.md | refunds or cancellations are implemented |
| DASHBOARD_AND_REPORTING.md, modules/dashboard.md, modules/reports.md | dashboard or reports are implemented |
| BULK_IMPORTS.md | any import is implemented |
| OFFLINE_READINESS.md | any offline-related code (device ids, idempotency keys, sync) is added |
| API_DOCUMENTATION.md | the first API route is added |
| modules/customers.md, modules/products.md | customers / products are implemented |
