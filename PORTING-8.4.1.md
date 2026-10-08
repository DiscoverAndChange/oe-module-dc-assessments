# Porting oe-module-dc-assessments to OpenEMR 8.4.1 (PHP 8.5)

Status as of the `ai/port-openemr-8.4.1` branch.

## Why
OpenEMR 8.4.1 runs **PHP 8.5** and ships **Symfony 7.4** + **Doctrine ORM 3.6 /
DBAL 4.4**. This module was written for OpenEMR 7.0.x (Symfony 5.4, Doctrine
2.x) and tightened-up core APIs now break it. Verified by running the module in
a real OpenEMR 8.4.1 container (not just static analysis).

## Phase 1 — DONE (this PR): module loads + logging
Verified in a live 8.4.1 container: the module **registers, bootstraps, compiles
its DI container**, and the **patient frontend entry point returns HTTP 200**.

Fixes:
1. **`src/Logging/LoggerAwareTrait.php`** — `$logger` is now
   `?Psr\Log\LoggerInterface` and `setLogger()` matches PSR's
   `setLogger(LoggerInterface): void`. 8.4.1's `FhirServiceBase` composes PSR's
   `LoggerAwareTrait`; the old untyped `$logger` caused a fatal property-type
   mismatch on every FHIR subclass.
2. **`src/Bootstrap.php`** — alias `Psr\Log\LoggerInterface` → the synthetic
   `logger` (SystemLogger) service so the `#[Required] setLogger()` autowires.
3. **FHIR services (4 files)** — `createOpenEMRSearchParameters()` overrides now
   match 8.4.1's typed base signature
   `(array $fhirSearchParameters, ?string $puuidBind = null): array`.
4. **`errorLogCaller()` → `error()`** (~70 call sites) — `SystemLogger` no longer
   has the custom `errorLogCaller()`; replaced with the PSR-3 `error()` method.

### Compatibility note
Phase 1 targets **OpenEMR >= 8.4**. It is **not** backward-compatible with 7.0.2:
8.4.1 added typed params to `FhirServiceBase::createOpenEMRSearchParameters`, and
PHP will not let a subclass narrow 7.0.x's untyped parameters. Do not deploy this
branch on a 7.0.2 server.

## Phase 2 — DONE: dependency modernization
The module bundled its own `vendor/` with **Symfony 5.4 / Doctrine 2–3**, which
collided in OpenEMR's single PHP process with core's **Symfony 7.4 / Doctrine
3.6 / DBAL 4.4** — the confirmed fatal on every REST/FHIR request was the
module's 5.4 `ResponseHeaderBag` extending core's 7.4 `HeaderBag`.

What was done (`composer.json`):
- Pin bundled Symfony to core's major (`^7.4`) and **cap the drift-prone
  transitives** (`http-foundation`, `string`, `console`, `type-info`,
  `var-exporter`) so composer can't pull Symfony 8. Same-major copies are
  ABI-compatible with core, and core's autoloader (registered first) wins at
  runtime anyway, so there is no fatal collision.
- `symfony/serializer` ^7.4 (core doesn't ship it); `FhirObjectDenormalizer`
  updated to the Symfony 7 `DenormalizerInterface` (typed `denormalize`/
  `supportsDenormalization` + new required `getSupportedTypes()`).
- **Dropped Doctrine entirely.** The module used only a dead DBAL `Connection`
  stub (`OpenEMRDatabaseConnectionWrapper`, removed) and two unused
  `Doctrine\ORM\Query` imports (removed); repositories use OpenEMR
  `QueryUtils` / raw SQL. This removes a whole collision vector.
- Regenerated `composer.lock` against PHP 8.5; bundled set trimmed from 42 to
  ~31 packages (serializer, property-info, reflection-docblock + Symfony 7.4
  runtime + unique sub-deps).

Verified live (8.4.1 container): bootstrap compiles, `fhir/metadata` → 200 with
the module's Questionnaire/QuestionnaireResponse resources, patient frontend →
200, `moduleConfig.php` → 200, no fatals/deprecations.

## Still needs human QA (can't be scripted here)
- Authenticated FHIR read/write (OAuth) for Questionnaire/QuestionnaireResponse/Task.
- Provider UI (appointment assignment, encounter screen) and the patient-portal
  assignment completion flow, end to end on populated data.
- Confirm the `Task` resource advertises/behaves as intended (it is not in the
  default capability statement — unchanged by this port).
- Run the module's PHPUnit suite against an 8.4.1 checkout.
- Symfony property-access note: core pins `property-access` at v4.4 while the
  bundled Serializer expects v7; at runtime core's v4.4 loads first. The common
  accessor API is stable, metadata/frontend work, but watch for edge cases in
  serializer-heavy paths during QA.

## How this was tested
OpenEMR 8.4.1 container + MariaDB, module bind-mounted into
`interface/modules/custom_modules/`, module activated in the `modules` table and
`table.sql` applied. The assessments `Bootstrap` writing `cache/container.php` is
the success signal that the DI container compiled.
