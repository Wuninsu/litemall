# MANDATORY REQUIREMENT: COMPREHENSIVE PROJECT DOCUMENTATION

## 1. OBJECTIVE

Every development task must produce or update relevant project documentation.

The application must be understandable and maintainable by future developers and AI coding agents who were not involved in its original development.

Documentation is part of the implementation, not an optional final activity.

Never implement important functionality without documenting how it works.

## 2. DOCUMENTATION STRUCTURE

Create and maintain the following documentation structure:

```text
docs/
├── README.md
├── PROJECT_OVERVIEW.md
├── ARCHITECTURE.md
├── DEVELOPMENT_GUIDE.md
├── DATABASE_SCHEMA.md
├── DATABASE_RELATIONSHIPS.md
├── BUSINESS_RULES.md
├── SERVICE_LAYER.md
├── LIVEWIRE_COMPONENTS.md
├── AUTHENTICATION_AND_AUTHORIZATION.md
├── SECURITY.md
├── FINANCIAL_LOGIC.md
├── SALES_MANAGEMENT.md
├── PAYMENT_SYSTEM.md
├── PROMOTIONS_SYSTEM.md
├── REFUNDS_AND_CANCELLATIONS.md
├── DASHBOARD_AND_REPORTING.md
├── BULK_IMPORTS.md
├── UUID_STRATEGY.md
├── OFFLINE_READINESS.md
├── API_DOCUMENTATION.md
├── TESTING_GUIDE.md
├── DEPLOYMENT_GUIDE.md
├── BACKUP_AND_RECOVERY.md
├── TROUBLESHOOTING.md
├── CHANGELOG.md
├── DECISIONS.md
└── modules/
    ├── customers.md
    ├── products.md
    ├── sales.md
    ├── payments.md
    ├── promotions.md
    ├── dashboard.md
    └── reports.md
```

Do not create empty documentation files merely to satisfy this list. Populate each document with accurate information about implemented functionality.

## 3. DOCUMENTATION CONTENT

### PROJECT_OVERVIEW.md

Document:

- What the application does.
- The business problem it solves.
- Main features.
- User roles.
- Technology stack.
- System boundaries.
- Current development status.
- Known limitations.
- Planned future improvements.

### ARCHITECTURE.md

Document:

- Overall application architecture.
- Architectural layers.
- Responsibilities of each layer.
- How Livewire communicates with services.
- How services interact with models.
- How business logic is shared between different entry points.
- Important architectural decisions.
- Design patterns used.

Include architecture diagrams using Mermaid where useful.

### DATABASE_SCHEMA.md

Document every database table.

For each table include:

- Table name.
- Purpose.
- Column names.
- Data types.
- UUID strategy.
- Primary keys.
- Foreign keys.
- Indexes.
- Unique constraints.
- Nullable fields.
- Default values.
- Relationships.

Keep this documentation synchronised with actual migrations.

### DATABASE_RELATIONSHIPS.md

Document:

- Entity relationships.
- One-to-one relationships.
- One-to-many relationships.
- Many-to-many relationships.
- Foreign key behaviour.
- Cascading and deletion rules.

Include an ER diagram using Mermaid.

### BUSINESS_RULES.md

Document all business rules in plain language.

Include:

- Customer registration rules.
- Product registration rules.
- Sale creation rules.
- Discount rules.
- Promotion eligibility rules.
- Payment allocation rules.
- Outstanding balance calculations.
- Refund rules.
- Cancellation rules.
- Historical pricing rules.
- Receipt numbering rules.

For every important rule, explain why it exists and where it is enforced in the codebase.

### SERVICE_LAYER.md

Document every service.

For each service include:

- Service name.
- File location.
- Responsibility.
- Public methods.
- Expected input.
- Expected output.
- Exceptions that may be thrown.
- Dependencies.
- Database operations.
- Related tests.
- Other services it interacts with.

Clearly document that business logic belongs in services and not Livewire components.

### LIVEWIRE_COMPONENTS.md

Document every Livewire component.

For each component include:

- Component name.
- File location.
- Purpose.
- Properties.
- Public actions.
- Services it calls.
- Events dispatched.
- Authorisation requirements.
- Validation rules.
- Related views.

### FINANCIAL_LOGIC.md

Document all financial calculations and accounting rules.

Include:

- Sale totals.
- Discounts.
- Promotional prices.
- Initial payments.
- Subsequent payments.
- Payment allocations.
- Outstanding balances.
- Refunds.
- Reversals.
- Cancelled sales.

Include worked examples using Ghana Cedis.

Explain how financial consistency is maintained during concurrent operations.

### SECURITY.md

Document:

- Authentication.
- Authorisation.
- Roles and permissions.
- Password security.
- Session security.
- CSRF protection.
- XSS protection.
- SQL injection protection.
- Rate limiting.
- File upload security.
- Financial operation protection.
- Audit logging.
- Sensitive data handling.

Include security assumptions and known limitations.

### OFFLINE_READINESS.md

Document:

- UUID strategy.
- Device identification.
- Local storage strategy.
- Planned IndexedDB structure.
- Service worker strategy.
- Synchronisation architecture.
- Idempotency strategy.
- Conflict resolution.
- Offline financial operation rules.
- Current implementation status.

Clearly distinguish implemented functionality from planned functionality.

### TESTING_GUIDE.md

Document:

- Testing framework.
- How to execute tests.
- Unit testing strategy.
- Feature testing strategy.
- Service testing strategy.
- Livewire testing strategy.
- Security testing strategy.
- Financial test scenarios.
- Test data preparation.

Include actual commands that work with the project.

### DEPLOYMENT_GUIDE.md

Document:

- Server requirements.
- PHP requirements.
- Database requirements.
- Environment configuration.
- Installation commands.
- Database migration commands.
- Build commands.
- Queue configuration where applicable.
- Cache configuration.
- HTTPS requirements.
- Production deployment procedure.

Never document invented or unverified commands.

## 4. ARCHITECTURAL DECISION RECORDS

Create an architecture decision log.

File:

```text
docs/DECISIONS.md
```

Whenever an important architectural decision is made, document:

- Decision ID.
- Date.
- Decision title.
- Problem being addressed.
- Available alternatives.
- Chosen solution.
- Reasons for choosing it.
- Advantages.
- Disadvantages.
- Potential future implications.

Examples:

- Why UUIDs are used.
- Why Livewire 4 is used.
- Why business logic resides in services.
- Why financial records are preserved.
- Why payments are allocated oldest-first.
- Why promotions use historical snapshots.
- Why the system is designed for future offline support.

Never silently change an important architectural decision.

## 5. AGENTS.md REQUIREMENTS

Maintain a comprehensive root-level AGENTS.md.

It must serve as the primary instruction document for future AI coding agents.

It should contain:

1. Project overview.
2. Technology stack.
3. Architecture rules.
4. Project directory structure.
5. Service-layer requirements.
6. UUID requirements.
7. Database conventions.
8. Financial business rules.
9. Security requirements.
10. Livewire development rules.
11. Testing requirements.
12. Documentation requirements.
13. Commands for development and testing.
14. Important files and documentation references.

Keep AGENTS.md concise enough to be practical.

Use docs/ as the detailed technical knowledge base.

If the repository contains additional nested AGENTS.md files, respect their scope and keep their instructions consistent.

## 6. DOCUMENTATION MAINTENANCE RULES

These rules are mandatory:

1. Whenever a database migration changes, update DATABASE_SCHEMA.md.
2. Whenever a model relationship changes, update DATABASE_RELATIONSHIPS.md.
3. Whenever a service is created or modified, update SERVICE_LAYER.md.
4. Whenever a Livewire component changes, update LIVEWIRE_COMPONENTS.md where its public behaviour or dependencies change.
5. Whenever a business rule changes, update BUSINESS_RULES.md.
6. Whenever financial calculations change, update FINANCIAL_LOGIC.md.
7. Whenever security behaviour changes, update SECURITY.md.
8. Whenever offline architecture changes, update OFFLINE_READINESS.md.
9. Whenever a major technical decision is made, update DECISIONS.md.
10. Whenever functionality is added or removed, update CHANGELOG.md.
11. Whenever installation or deployment instructions change, update the relevant guides.

Documentation must describe actual implemented behaviour, not assumptions.

## 7. CHANGELOG

Maintain a chronological CHANGELOG.md.

Use the following format:

```markdown
# Changelog

## [Unreleased]

### Added
- Description of new functionality.

### Changed
- Description of modified functionality.

### Fixed
- Description of bug fixes.

### Security
- Description of security-related changes.
```

Do not overwrite previous release history.

## 8. DEVELOPMENT HANDOVER

At the completion of every major development phase, produce a handover summary.

Include:

- What was implemented.
- Important files created.
- Important files modified.
- Database changes.
- Services introduced.
- Livewire components introduced.
- Business rules implemented.
- Tests executed.
- Test results.
- Known bugs.
- Outstanding tasks.
- Architectural decisions.
- Recommended next development phase.

Maintain a current project status document:

```text
docs/PROJECT_STATUS.md
```

This document must tell a future agent:

- What is complete.
- What is in progress.
- What is not yet implemented.
- What needs attention.
- What the next recommended task is.

Update it after every significant development milestone.

## 9. FUTURE AGENT ONBOARDING

A future AI coding agent must be able to start by reading:

1. AGENTS.md
2. docs/PROJECT_OVERVIEW.md
3. docs/ARCHITECTURE.md
4. docs/PROJECT_STATUS.md
5. Relevant module documentation.

It should then inspect the actual code before making changes.

It must never assume documentation is more accurate than the implementation.

If documentation and code disagree:

- Identify the discrepancy.
- Investigate the actual intended behaviour.
- Correct the documentation or implementation.
- Record important changes.

## 10. FINAL RULE

No major feature is considered complete until:

- Its implementation is finished.
- Its tests pass.
- Its relevant documentation is updated.
- Its business rules are documented.
- Its architectural implications are recorded where necessary.

The ultimate objective is to create a self-documenting, maintainable and extensible application that any competent future developer or AI agent can understand, improve and safely extend without having to reverse-engineer the entire system.