# HomeWatt Engineering Standards

This file is the project-wide engineering contract for humans and coding
agents working on HomeWatt.

Read this file completely before changing the repository. Also read
`HOMEWATT_IMPLEMENTATION_PLAN.md`, then inspect the real repository state.
Never assume a module, route, model, migration, service, container, or test
already exists merely because it is described in the implementation plan.

The keywords MUST, MUST NOT, SHOULD, SHOULD NOT, and MAY are normative.

## 1. Product Mission

HomeWatt helps households catalog electrical devices, extract device
information from photos, record measured or declared power data, estimate
monthly electricity consumption, and explain the resulting cost.

The core product principles are:

1. AI proposes information; a user confirms it.
2. Photos are private by default.
3. Measured data and estimated data are never presented as equivalent.
4. Every estimate must be explainable from its inputs and method.
5. Electricity tariffs are versioned data, not hardcoded constants.
6. Every household-owned record is protected by home-level authorization.
7. Mobile camera and review flows are first-class product experiences.
8. Documentation and tests are part of the definition of done.

## 2. Current Repository State

At the time this standard was created, HomeWatt had not yet been scaffolded
as a Laravel application. The repository contained planning and agent
documentation only.

Before beginning any task, agents MUST:

1. Run a file/status inspection such as `Get-ChildItem -Force` and
   `git status -sb` when Git is available.
2. Determine which implementation phase has actually been completed.
3. Inspect dependency lockfiles before claiming exact framework or package
   versions.
4. Avoid commands requiring `artisan`, Composer dependencies, Node
   dependencies, Docker files, or module files until those files exist.
5. Preserve user-created files and unrelated worktree changes.

The implementation roadmap is authoritative for product scope:
`HOMEWATT_IMPLEMENTATION_PLAN.md`.

## 3. Target Technical Baseline

Unless the repository has adopted and documented a newer compatible version:

- Runtime: PHP 8.4 and Laravel 12.
- Architecture: modular monolith using `nwidart/laravel-modules`.
- UI: Blade, Alpine.js, Tailwind CSS, and Vite.
- Database: MySQL 8 in production and CI.
- Cache and queues: Redis 7.
- Web server: Nginx in front of PHP-FPM.
- Testing: PHPUnit with Laravel testing utilities.
- Formatting: Laravel Pint.
- Production build: immutable multi-stage Docker images.
- CI: tests, formatting, dependency audit, frontend build, and Compose
  validation.

Exact package versions MUST come from `composer.lock` and `package-lock.json`
after scaffolding. Documentation MUST not contradict those lockfiles.

## 4. Fixed Runtime and Port Contract

These host ports are reserved for HomeWatt:

| Service | Host port | Container port |
| --- | ---: | ---: |
| Nginx web | `8087` | `80` |
| MySQL | `3311` | `3306` |
| Redis | `6384` | `6379` |
| PHP-FPM | Not public | `9000` |

Required defaults:

```env
APP_NAME=HomeWatt
APP_URL=http://localhost:8087
APP_HTTP_PORT=8087
APP_RELEASE=local

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=homewatt
DB_FORWARD_PORT=3311

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_FORWARD_PORT=6384

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database
```

Rules:

- Laravel containers MUST connect to `db:3306`, not host port `3311`.
- Laravel containers MUST connect to `redis:6379`, not host port `6384`.
- Host-forwarded ports exist for local tools and MUST NOT be used for
  container-to-container communication.
- Container names, networks, images, and volumes MUST use a `homewatt`
  prefix to avoid conflicts with sibling self-hosted projects.
- Secrets and real passwords MUST remain in `.env` or a secret store and MUST
  never be committed.

## 5. Target Docker Service Layout

The production Compose stack should contain:

| Service | Responsibility |
| --- | --- |
| `app` | PHP-FPM request processing |
| `nginx` | Public HTTP entrypoint |
| `queue` | Default background work |
| `queue-ai` | Slow and costly AI image analysis |
| `scheduler` | Scheduled summaries, cleanup, and alerts |
| `db` | MySQL 8 with a named volume |
| `redis` | Cache, queue, and rate-limit state |

Each long-running service MUST have a meaningful healthcheck. Deployments
that change queued code MUST restart workers.

Production releases MUST expose:

- `/up` for application health.
- `/version` for the current `APP_RELEASE`.

Deploy verification MUST include application health, release identity, login
page availability, and at least one built frontend asset. Production
deployment should preserve a previous image tag for rollback.

## 6. Module Ownership

The target modules and their responsibilities are:

| Module | Responsibility |
| --- | --- |
| `Core` | Shared UI, application support, health/version presentation |
| `Home` | Homes, memberships, roles, and home access |
| `Room` | Rooms and spatial grouping within a home |
| `Device` | Device catalog, device types, and specifications |
| `Media` | Private photos, metadata, authorization, and lifecycle |
| `AI` | Vision providers, analysis jobs, extraction schema, usage/cost |
| `Energy` | Usage profiles, readings, estimates, and calculation methods |
| `Tariff` | Versioned tariff plans, tiers, taxes, and effective dates |
| `Dashboard` | Aggregates, charts, rankings, and data-quality indicators |
| `Admin` | Reference data, tariffs, AI usage, and operational controls |

A module MUST own its routes, controllers, requests, policies, services,
models, migrations, views, tests, configuration, and README for its business
capability.

Before editing an existing module, agents MUST read its README and inspect its
routes, models, policies, requests, services/actions, migrations, and focused
tests.

Cross-module access MUST use public contracts, actions, services, events,
jobs, or models with clearly documented boundaries. A module MUST NOT reach
into another module's internal views or private implementation details.

## 7. Architecture and Layer Responsibilities

- Routes map HTTP method, URI, middleware, name, and controller only.
- Form Requests authorize simple request context, validate input, and
  normalize boundary values.
- Policies and Gates enforce resource authorization.
- Controllers coordinate request, authorization, action/service, and
  response.
- Actions represent one stateless business use case.
- Services group multiple closely related domain operations.
- Jobs handle slow, retryable, or asynchronous workflows.
- Models own relationships, casts, local scopes, and small invariants.
- Query objects or repositories exist only for genuinely complex, reused
  persistence logic.
- Views and Blade components contain presentation logic only and MUST NOT
  query the database.

Controllers SHOULD remain thin. Multi-record writes MUST have an explicit
transaction boundary.

### 7.1 Actions and services

- Prefer an Action for a new single-purpose mutation, such as
  `ConfirmDeviceExtractionAction`.
- Use a Service when several related operations share dependencies or state,
  such as `EnergyEstimationService`.
- Dependencies MUST use constructor injection.
- Domain code MUST NOT use `app()->make()` as a service locator.
- External I/O, persistence, and orchestration SHOULD be separable for
  testing.

### 7.2 Repository rule

Laravel Eloquent is sufficient for straightforward CRUD. Do not create empty
repository interfaces.

Use a repository or query object only when:

- A non-trivial query is reused in multiple places.
- A read combines several authorization, date, aggregate, or relationship
  concerns.
- Persistence needs a real swappable boundary.

Methods must express intent, such as `summarizeConsumptionForHome()`, rather
than mirror SQL syntax.

## 8. Home-Level Data Isolation

`home_id` is the primary tenant boundary for household data.

The expected hierarchy is:

```text
User
  -> HomeMembership
      -> Home
          -> Room
              -> Device
                  -> Media
                  -> DeviceSpecification
                  -> DeviceUsageProfile
                  -> EnergyReading
                  -> EnergyEstimate
```

Rules:

- Authentication is not authorization.
- Every protected operation MUST verify membership and the requested ability.
- List queries MUST be scoped to homes the current user may access.
- Route model binding does not replace policy authorization.
- IDs supplied by a client are never proof of ownership.
- Cross-home create, read, update, delete, download, and AI-analysis requests
  MUST be covered by tests.
- Prefer `404` when revealing another home's resource existence would leak
  information.
- Role values SHOULD use a backed enum such as `HomeRole`.
- An admin bypass, if introduced, MUST be explicit, centralized, and tested.

Expected membership roles:

- `owner`: full control, membership and destructive settings.
- `manager`: manage rooms, devices, measurements, and estimates.
- `member`: contribute device and usage data as allowed.
- `viewer`: read-only access.

Do not implement role behavior from this list blindly; define and test the
actual policy matrix when the Home module is created.

## 9. Device and Specification Rules

A device record must distinguish different kinds of electrical information.
Do not collapse all power information into a single ambiguous
`power_watts` field.

At minimum, the domain should be able to distinguish:

- Rated power.
- Maximum power.
- Standby power.
- Measured instantaneous power.
- Measured energy over an interval.
- Adjusted or estimated average power.

Rules:

- Store canonical numeric values in explicit units.
- Preserve the original extracted text for traceability.
- Record the source of each important value: user, AI, label, smart plug,
  meter, import, or derived calculation.
- A confirmed value MUST NOT be silently overwritten by a later AI result.
- Device type defaults are suggestions, not measured facts.
- Unknown values remain unknown; do not replace them with invented zeros.
- Serial numbers and detailed device photos may be sensitive and must not be
  exposed unnecessarily.

## 10. Media and Photo Security

Device and label photos are private by default.

All upload flows MUST:

1. Authorize access to the target home/device.
2. Enforce a configured file count and byte limit.
3. Validate the actual file type and image decodability.
4. Generate server-controlled storage names.
5. Store bytes through Laravel's `Storage` abstraction.
6. Store metadata separately from the physical object.
7. Serve files through an authorized controller or short-lived signed URL.
8. Define cleanup behavior for failed uploads and deleted records.

Never:

- Trust the client filename or client-provided MIME type.
- Concatenate user input into a filesystem path.
- Expose a local storage path in JSON or HTML.
- Put private uploads under a permanently public URL.
- Log image bytes, base64 payloads, or signed download URLs.

Photo categories SHOULD distinguish at least:

- Device overview.
- Specification label.
- Energy label.
- Meter or smart-plug display.
- Other supporting evidence.

The category matters because a rated specification label and a live meter
reading have different meanings.

## 11. AI Vision Contract

AI output is untrusted external input.

The AI module MUST expose a provider-neutral contract such as
`DeviceImageAnalyzer`. OpenAI, Gemini, or another provider must map to the
same versioned output schema.

The analysis flow is:

1. User uploads or selects authorized private photos.
2. Application creates a pending analysis request.
3. `AnalyzeDeviceImageJob` runs on the `ai` queue.
4. The provider returns structured output.
5. The backend validates schema, types, units, and plausible ranges.
6. Proposed values and field-level confidence are stored separately.
7. The user reviews, edits, and confirms proposed values.
8. Only confirmed values update official device specifications.

Required rules:

- AI MUST NOT directly overwrite confirmed device data.
- Unclear fields MUST return `unknown`/`null`, not a guess represented as
  fact.
- Confidence SHOULD be stored per field.
- Prompts and responses MUST use a versioned schema.
- Normalize units such as W, kW, V, A, kWh, and kWh/year.
- Do not send home addresses, member names, or unrelated metadata.
- Rate limit AI requests per user and home.
- Track provider, model, token usage, cost, latency, status, and safe error
  context.
- Use checksums or an equivalent strategy to avoid accidental duplicate
  analysis.
- Retries MUST be idempotent and use backoff.
- Permanent failure MUST be visible and retryable by an authorized user.
- Automated tests MUST fake provider HTTP calls.
- API keys MUST come from configuration and MUST never be logged or stored in
  application tables.

## 12. Energy Calculation Rules

Every energy result MUST identify its method and source quality.

### 12.1 Basic formulas

For a relatively constant load:

```text
kWh = watts * usage_hours * usage_days / 1000
```

For a cycling load:

```text
kWh = watts * usage_hours * usage_days * duty_cycle / 1000
```

These formulas are starting points, not universal truth. Refrigerators, air
conditioners, water heaters, washing machines, and variable-speed appliances
often need a duty cycle, cycle model, manufacturer annual consumption, or
measured data.

### 12.2 Source precedence

When time ranges and quality are comparable, prefer:

1. Measured interval energy (`kWh`).
2. Repeated measurements or a calibrated average.
3. Manufacturer annual energy consumption.
4. Rated power plus an explicit usage profile and duty cycle.
5. Device-type default assumptions.

Never silently substitute one method for another.

### 12.3 Explainability

Each saved estimate MUST preserve enough information to reproduce it:

- Device and home.
- Calculation period.
- Method/version.
- Source values and units.
- Usage hours/days.
- Duty cycle or type assumption.
- Result in kWh.
- Low/high range when uncertainty is material.
- Confidence/data-quality level.
- Tariff version used for cost.
- Calculation timestamp.

Use decimal-safe arithmetic for energy and money. Define rounding at output
boundaries; do not repeatedly round intermediate calculations.

## 13. Tariff and Cost Rules

Electricity pricing MUST be represented as versioned database data.

A tariff plan should support:

- Provider and region.
- Currency.
- Effective start and end dates.
- Tier boundaries.
- Price per kWh.
- Taxes, fees, or adjustments where applicable.

Rules:

- Do not hardcode the current electricity price in controllers or views.
- Past estimates MUST retain the tariff snapshot/version used.
- Tier calculations MUST be tested at every boundary.
- Overlapping effective versions MUST be rejected or explicitly resolved.
- Currency formatting must be locale-aware.
- Cost output must state whether it is an estimate and what charges are
  included.

Tariff changes are temporally sensitive. Verify the source and effective date
before updating production tariff data.

## 14. Input Validation and Model Safety

- Use allowlists for enums, filters, sort fields, state transitions, MIME
  types, and units.
- Validate syntax and business meaning.
- Use `$request->validated()` or `$request->safe()`, never
  `$request->all()` for persistence.
- Normalize units and decimal formats at the boundary.
- Numeric input MUST have realistic minimums and maximums.
- Client validation is UX only; repeat it on the server.
- Models MUST define intentional `$fillable` fields or a justified guarded
  strategy.
- `$guarded = []` is forbidden.
- Ownership, roles, confirmation state, provider status, cost, and derived
  fields MUST be assigned by trusted server logic.
- Use backed enums for stable finite states.

## 15. Database and Migration Rules

- Production behavior is defined against MySQL 8.
- SQLite MAY be used for database-independent tests, but MySQL-specific
  behavior requires MySQL coverage.
- Foreign keys and indexes MUST reflect ownership and common queries.
- Use `decimal` columns for money and precision-sensitive energy values;
  do not use floats for billing calculations.
- Store timestamps in UTC and present them in the home's timezone.
- Multi-record workflows MUST use transactions.
- Migrations MUST be safe for existing data and production deployment.
- Destructive schema changes require a documented migration/backfill plan.
- Seeders provide reference/demo data; production backfills belong in
  migrations or explicit idempotent commands.
- Tariff effective dates and estimate snapshots MUST remain auditable.

## 16. Queue and Scheduler Rules

Slow or retryable work belongs in queues:

- AI image analysis.
- Image conversion or metadata extraction.
- Large exports.
- Notifications.
- Aggregate recalculation when not suitable for synchronous execution.

Every queued Job MUST define appropriate retry, timeout, and backoff behavior.
Jobs MUST re-check current authorization-relevant state and MUST be
idempotent.

The dedicated `queue-ai` worker MUST consume the `ai` queue. AI jobs must not
block ordinary user-facing background work.

The scheduler may own:

- Monthly summary generation.
- Orphaned media cleanup.
- Retry/reconciliation tasks.
- Data-quality reminders.

Scheduled work MUST use overlap protection when concurrent execution could
duplicate or corrupt results.

## 17. Security and Privacy

Use Laravel security primitives and OWASP guidance as the minimum baseline.

- Protected routes require authentication.
- Every resource operation requires object-level authorization.
- Login, uploads, AI, expensive reports, and public APIs require rate limits.
- Cookies in production use HTTPS, `Secure`, `HttpOnly`, and an appropriate
  `SameSite` policy.
- Passwords use Laravel hashing.
- Output is encoded for its context.
- Raw SQL, shell commands, file paths, URLs, and logs must not receive
  unsanitized input.
- Logs MUST avoid secrets, raw images, prompt payloads containing personal
  data, and unnecessary serial numbers.
- Errors shown to users must be safe; detailed provider errors belong in
  protected logs with redaction.
- Backup strategy must include MySQL and private media.
- Restore procedures must be tested, not merely documented.

Deleting a home, device, or photo requires an explicit retention and cascade
decision. Do not hard-delete audit-relevant energy history accidentally.

## 18. Frontend and Mobile UX

The primary capture flow is mobile-first.

- Camera inputs should support direct capture where browsers allow it.
- Show guidance for device overview and close-up label photos.
- Preserve upload progress and clear failure/retry states.
- AI processing must show pending, processing, completed, and failed states.
- Review screens should display the source photo beside proposed fields.
- Confidence and data source must be understandable without technical jargon.
- Users must be able to correct every AI-proposed field.
- Estimates must show the formula/method in plain language.
- Tables, forms, charts, and dialogs must remain usable on small screens.
- Interactive controls require keyboard support, focus styles, accessible
  names, contrast, loading states, and disabled states.
- Respect reduced-motion preferences.

Blade output uses escaped `{{ }}` by default. Use shared Core components
before duplicating UI patterns. Blade views MUST NOT access the database.

Alpine state should stay local to the smallest useful component. Prevent
duplicate submission while uploads, confirmations, or recalculations are in
progress.

## 19. Internationalization

The initial product should support Vietnamese and English.

- User-facing strings SHOULD use translation keys.
- New shared features must update both `lang/vi` and `lang/en`.
- Dates, times, numbers, kWh values, and currencies must use locale-aware
  formatting.
- Internally store canonical units and UTC timestamps.
- Translation keys should be semantic, for example
  `devices.analysis.confirm_title` or `energy.estimate.method.measured`.

## 20. API Rules

- Use stable noun-based resource URLs.
- Use route model binding followed by policy authorization.
- JSON endpoints use API Resources rather than exposing arbitrary Eloquent
  models.
- Hidden/internal fields and storage paths MUST not leak.
- Paginated endpoints cap page size.
- Validation errors should retain Laravel's standard `422` shape unless a
  versioned contract says otherwise.
- Retried state-changing operations should be idempotent.
- Register only implemented routes and controller actions.
- Breaking API changes require versioning or a migration plan.

## 21. Naming Conventions

Use names that express HomeWatt domain intent:

| Type | Example |
| --- | --- |
| Contract | `DeviceImageAnalyzer` |
| Action | `ConfirmDeviceExtractionAction` |
| Service | `EnergyEstimationService` |
| Job | `AnalyzeDeviceImageJob` |
| Event | `DeviceExtractionConfirmed` |
| Listener | `RecalculateDeviceEstimate` |
| Policy | `DevicePolicy` |
| Request | `StoreEnergyReadingRequest` |
| DTO | `DeviceExtractionData` |
| Enum | `EnergyReadingSource` |

Avoid vague names such as `process()`, `handleData()`, `$item`, `$info`, or
`$result` when a domain-specific name is available.

PHP classes and methods should have parameter and return types where Laravel
contracts permit. Prefer early returns and explicit dependencies. Comments
explain why or document a constraint; they do not narrate obvious code.

## 22. Testing Standard

Every behavior change requires tests proportional to risk.

Required coverage where applicable:

- Happy path.
- Validation failures and numeric boundaries.
- Unauthenticated and unauthorized requests.
- Cross-home IDOR attempts.
- File MIME, size, corrupt image, and cleanup behavior.
- AI success, malformed schema, low confidence, provider failure, retry, and
  duplicate analysis.
- User confirmation and protection against AI overwrite.
- Energy formulas, units, duty-cycle bounds, source precedence, and rounding.
- Tariff tiers, effective dates, taxes/fees, and boundary kWh values.
- Queue dispatch, idempotency, and failed-job state.
- Dashboard aggregate correctness and query efficiency.

Use:

- Factories for domain records.
- `Http::fake()` for AI providers.
- `Queue::fake()` where dispatch behavior is under test.
- `Storage::fake()` for media tests.
- Time travel for effective dates and monthly summaries.
- `RefreshDatabase` or database transactions for isolation.

Automated tests MUST NOT call real AI providers or rely on network access.

## 23. Minimum Verification

Run only commands supported by the current repository state.

After Laravel scaffolding, the standard verification bundle is:

```powershell
php artisan test
php vendor/bin/pint --test
php artisan route:list
php artisan view:cache
npm.cmd run build
git diff --check
```

When PowerShell execution policy blocks `npm.ps1`, use `npm.cmd`.

For Docker/deployment changes also run, when Docker is available:

```powershell
docker compose config --quiet
docker compose build
docker compose up -d --wait
docker compose ps
```

If Docker is unavailable locally, state that clearly. Perform static file
validation and rely on CI/Linux for actual image and Compose execution.

Focused tests should run before the full suite. Do not claim verification that
was not actually executed.

## 24. Performance Rules

- Prevent N+1 queries with eager loading.
- Paginate unbounded user-visible lists.
- Do not issue aggregate queries inside loops.
- Queue slow external work.
- Cache only stable, measured expensive reads.
- Cache keys must include the home/access scope.
- Mutations must invalidate affected aggregates.
- Dashboard summaries may use precomputed monthly aggregates once live
  queries become expensive.
- Do not optimize by weakening authorization, validation, auditability, or
  calculation correctness.

## 25. Git and Change Discipline

- Keep changes scoped to the requested task.
- Preserve unrelated worktree changes.
- Never commit `.env`, credentials, private photos, logs, dumps, or generated
  secrets.
- Update `.env.example` with safe placeholders for new configuration.
- Use clear migration names and coherent commits.
- Do not use destructive Git commands unless the user explicitly requests
  them.
- Do not claim the repository is deployed merely because code was committed
  or pushed; verify `/version` in production.

## 26. Documentation Rules

Every implemented module MUST have `Modules/<Module>/README.md` documenting:

- Responsibility and boundaries.
- Important routes and entry points.
- Models and relationships.
- Business workflow.
- Authorization rules.
- Configuration.
- Queue/scheduler behavior.
- Operational and verification commands.

Behavior, schema, configuration, or deployment changes MUST update the
relevant documentation in the same task.

`HOMEWATT_IMPLEMENTATION_PLAN.md` describes the roadmap. This file describes
engineering rules. Module READMEs describe implemented reality. When they
conflict, inspect the code and update stale documentation rather than
silently choosing one.

## 27. Definition of Done

A change is complete only when all applicable items pass:

- [ ] The real repository state and relevant module README were inspected.
- [ ] The change belongs to the correct module.
- [ ] Input validation covers types, ranges, semantics, and allowlists.
- [ ] Authentication and policy authorization are enforced server-side.
- [ ] Home-level data isolation is tested.
- [ ] Photos remain private and file lifecycle is handled safely.
- [ ] AI output is validated and cannot overwrite confirmed facts.
- [ ] Energy results preserve method, source, units, and reproducible inputs.
- [ ] Tariff version/effective date is explicit.
- [ ] Transactions, retries, and idempotency are defined where needed.
- [ ] Queries avoid obvious N+1 and unbounded reads.
- [ ] Sensitive data is absent from responses and logs.
- [ ] Tests cover happy path, failures, authorization, and regressions.
- [ ] Relevant module documentation and `.env.example` are updated.
- [ ] Pint, tests, route/view checks, frontend build, and diff checks pass as
      applicable.
- [ ] Queue workers, migrations, backup, or deployment steps are documented
      when applicable.

## 28. Agent Workflow

For implementation tasks, agents should follow this order:

1. Read this file and the implementation plan.
2. Inspect the current repository and Git status.
3. Read relevant module documentation and code.
4. State assumptions briefly when the repository does not answer them.
5. Implement the smallest coherent production-quality slice.
6. Add or update tests and documentation.
7. Run focused verification, then broader verification proportional to risk.
8. Report completed work, files changed, verification performed, and any
   remaining limitation.

For review or diagnosis requests, inspect and report evidence first. Do not
make unrelated changes unless the user asks for implementation.

## 29. Authoritative References

- HomeWatt roadmap: `HOMEWATT_IMPLEMENTATION_PLAN.md`
- Laravel 12: https://laravel.com/docs/12.x
- Laravel authorization:
  https://laravel.com/docs/12.x/authorization
- Laravel validation: https://laravel.com/docs/12.x/validation
- Laravel filesystem: https://laravel.com/docs/12.x/filesystem
- Laravel queues: https://laravel.com/docs/12.x/queues
- Laravel rate limiting:
  https://laravel.com/docs/12.x/rate-limiting
- OWASP Laravel Cheat Sheet:
  https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html
- OWASP File Upload Cheat Sheet:
  https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
- OWASP IDOR Prevention Cheat Sheet:
  https://cheatsheetseries.owasp.org/cheatsheets/Insecure_Direct_Object_Reference_Prevention_Cheat_Sheet.html

## 30. Change Log

- 2026-06-21: Established the HomeWatt engineering baseline with domain
  boundaries, fixed ports, private media, AI confirmation, energy
  calculation, tariff versioning, mobile capture, and pre-scaffold repository
  guidance.
