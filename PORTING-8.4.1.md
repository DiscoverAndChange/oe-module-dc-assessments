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

## Phase 2 — TODO: dependency modernization (REST/FHIR still 500s without it)
The module bundles its own `vendor/` with **Symfony 5.4** and **Doctrine 2–3**.
In OpenEMR's single PHP process these collide with core's **Symfony 7.4** /
**Doctrine 3.6 / DBAL 4.4** — e.g. the confirmed fatal on any REST/FHIR request:

```
Declaration of Symfony\...\ResponseHeaderBag::all(?string $key = null) must be
compatible with Symfony\...\HeaderBag::all(?string $key = null): array
```
(module's Symfony 5.4 `ResponseHeaderBag` extending core's Symfony 7.4 `HeaderBag`).

Required work:
- Bump `composer.json` to the versions 8.4.1 core uses so the bundled copies are
  identical to core (no collision): `symfony/* ^7.4`, `symfony/psr-http-message-bridge ^7.4`.
  `symfony/serializer` and `phpdocumentor/reflection-docblock` are **not** in core,
  so keep bundling them — but at Symfony 7.
- Doctrine: core has ORM 3.6 / DBAL 4.4. Reconcile `src/Doctrine/OpenEMRDatabaseConnectionWrapper.php`
  and any ORM/DBAL usage; bump to ORM ^3 / DBAL ^4 (or drop if unused — repositories
  otherwise use OpenEMR `QueryUtils`/raw SQL).
- `composer update` to regenerate the lock against PHP 8.5 + the new constraints.
- Code audit for Symfony 5.4 → 7 API changes, focus areas by import count:
  Serializer (14), DependencyInjection (12), Routing (5), PropertyInfo (2),
  EventDispatcher (21, mostly stable), `HttpFoundationFactory` in `APIProxyController.php`.
- Re-run the module's PHPUnit suite and exercise FHIR (Questionnaire,
  QuestionnaireResponse, Task) + the provider UI + patient portal end to end.

## How this was tested
OpenEMR 8.4.1 container + MariaDB, module bind-mounted into
`interface/modules/custom_modules/`, module activated in the `modules` table and
`table.sql` applied. The assessments `Bootstrap` writing `cache/container.php` is
the success signal that the DI container compiled.
