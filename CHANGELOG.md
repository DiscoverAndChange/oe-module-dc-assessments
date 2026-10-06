v0.11.0 OpenEMR 8.4.1 (PHP 8.5) compatibility

  Port the module to OpenEMR 8.4.1 / PHP 8.5. Verified in a live 8.4.1 container:
  the module registers and bootstraps, the FHIR capability endpoint serves with
  the module's Questionnaire/QuestionnaireResponse resources, and the patient
  frontend and backend config pages load — with no fatals or module deprecations.

  Core API alignment:
  - LoggerAwareTrait: type the $logger property as ?Psr\Log\LoggerInterface and
    align setLogger() to PSR's setLogger(LoggerInterface): void, so it no longer
    clashes with the PSR LoggerAwareTrait that 8.4.1's FhirServiceBase composes.
  - Bootstrap: alias Psr\Log\LoggerInterface to the synthetic SystemLogger
    service so the #[Required] setLogger() setter autowires.
  - FHIR services: match 8.4.1's typed FhirServiceBase::createOpenEMRSearchParameters
    signature (array $fhirSearchParameters, ?string $puuidBind = null): array.
  - Replace SystemLogger::errorLogCaller() (removed in 8.4.1) with PSR-3
    LoggerInterface::error() across the module (~70 call sites).

  Dependency modernization (fixes the in-process class collisions that broke
  every REST/FHIR request):
  - Pin bundled Symfony to the same major core ships (^7.4) and cap the
    transitive packages (http-foundation, string, console, type-info,
    var-exporter) so they cannot drift onto Symfony 8.
  - symfony/serializer: upgrade to ^7.4; update FhirObjectDenormalizer to the
    Symfony 7 DenormalizerInterface (typed signatures + getSupportedTypes()).
  - Drop Doctrine entirely (ORM/DBAL) — the module uses OpenEMR QueryUtils / raw
    SQL, not Doctrine; removed the unused OpenEMRDatabaseConnectionWrapper and the
    dead Doctrine\ORM\Query imports.

  NOTE: targets OpenEMR >= 8.4 and is NOT backward-compatible with 7.0.2 (8.4.1
  tightened FhirServiceBase with typed signatures that 7.0.x's untyped base
  rejects). Authenticated FHIR read/write, the full provider UI, and the patient
  portal assignment flow still need end-to-end QA on a populated 8.4.1 instance.
  See PORTING-8.4.1.md.

v0.10.0 Add developer tooling, CLAUDE.md, and project conventions

  Add composer scripts for phpstan, rector, and phpunit that run from the OpenEMR root.
  Add CLAUDE.md with architecture docs, commands, and coding conventions.
  Establish versioning, branching, and PR conventions for AI-assisted development.

v0.9.0 Initial module release

  Release of working assessment module.
