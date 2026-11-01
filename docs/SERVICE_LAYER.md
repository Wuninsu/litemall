# Service Layer

**Business logic belongs in services, not in Livewire components, controllers, models, or Blade views.** (ADR-0004, Proposed.)

## Current services

None. The `app/Services` directory does not exist yet.

## Conventions for new services

- Location: `app/Services/<Name>Service.php`, created with `php artisan make:class Services/<Name>Service --no-interaction`.
- Dependencies via constructor property promotion; resolve services from the container (inject into Livewire actions/controllers), never `new` them in entry points.
- Public methods take typed scalars, DTOs or models and return models/value objects — never HTTP responses or arrays of view data.
- Throw specific domain exceptions (e.g. `App\Exceptions\Sales\…`) for rule violations; entry points translate them for the user.
- Wrap multi-row writes in `DB::transaction()`; use `lockForUpdate()` when reading a balance that will be written.
- Each service has a matching test file under `tests/Feature/Services/` or `tests/Unit/Services/`.

## Catalogue entry template

Copy this for every service added:

```markdown
### <ServiceName>
- **File:** app/Services/<ServiceName>.php
- **Responsibility:**
- **Dependencies:**
- **Interacts with services:**
- **Related tests:**

| Method | Input | Output | Throws | DB operations |
|---|---|---|---|---|
```
