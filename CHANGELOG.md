v0.11.0 OpenEMR 8.4.1 (PHP 8.5) compatibility — phase 1 (module load + logging)

  Make the module load and bootstrap cleanly on OpenEMR 8.4.1 / PHP 8.5, and
  restore logging against the 8.4.1 core API. The patient-facing frontend entry
  point now loads on 8.4.1.
  - LoggerAwareTrait: type the $logger property as ?Psr\Log\LoggerInterface and
    align setLogger() to PSR's signature, so it no longer clashes with the
    PSR LoggerAwareTrait that 8.4.1's FhirServiceBase now composes.
  - Bootstrap: alias Psr\Log\LoggerInterface to the synthetic SystemLogger
    service so the #[Required] setLogger() setter autowires on 8.4.1.
  - FHIR services: match 8.4.1's typed FhirServiceBase::createOpenEMRSearchParameters
    signature (array $fhirSearchParameters, ?string $puuidBind = null): array.
  - Replace SystemLogger::errorLogCaller() (removed in 8.4.1) with the PSR-3
    LoggerInterface::error() method across the module (~70 call sites).
  NOTE: This phase targets OpenEMR >= 8.4 and is NOT backward-compatible with
  7.0.2 (8.4.1 tightened FhirServiceBase with typed signatures that 7.0.x's
  untyped base rejects). REST/FHIR endpoints are NOT yet functional on 8.4.1 —
  the module still bundles Symfony 5.4 / Doctrine 2-3, which collide in-process
  with core's Symfony 7.4 / Doctrine 3.6+dbal 4. See PORTING-8.4.1.md. That
  dependency modernization is phase 2.

v0.10.0 Add developer tooling, CLAUDE.md, and project conventions

  Add composer scripts for phpstan, rector, and phpunit that run from the OpenEMR root.
  Add CLAUDE.md with architecture docs, commands, and coding conventions.
  Establish versioning, branching, and PR conventions for AI-assisted development.

v0.9.0 Initial module release

  Release of working assessment module.
