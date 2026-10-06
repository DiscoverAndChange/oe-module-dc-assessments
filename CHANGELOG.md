v0.11.1 PHPStan level-10 fixes (bugs + non-ignorable) and module-local baseline

  Fix the genuine bugs and all non-ignorable errors PHPStan surfaced once the
  module could be analyzed in isolation on 8.4.1; baseline the remaining
  pre-existing style debt so CI can run green and the debt is burned down later.

  Latent bugs:
  - Client::sortAssignmentsByDateAssigned() / fromJSON: `new DateTime()`,
    `new InvalidArgumentException`, and SystemUser::fromJSON `new Exception`
    resolved into the module's Models namespace (non-existent classes) — qualify
    them as \DateTime / \InvalidArgumentException / \Exception.
  - RestUtils: add getAccessDeniedResponse() and getServerErrorResponse(), which
    AssessmentGroup/AssessmentReport/AssessmentResult controllers call on their
    access-denied / error paths but which did not exist (latent fatals).
  - AssessmentGroupRestController: declare the $logger property (was an undeclared
    dynamic property, deprecated on PHP 8.5) typed as SystemLogger.
  - APISetupController: fix the RestApiScopeEvent closure param casing.
  - SystemError: its constructor was misspelled `__constructor` (so `new
    SystemError($code, $message)` silently dropped its arguments and left the
    typed `$_code`/`$_subErrors` properties uninitialized) — rename to
    `__construct`, assign both properties, default `$subErrors` to null, and
    correct `code()` to return int. This also unbreaks the live
    `throw new SystemError(...)` in ClientSearchRepository::getClientList(), which
    additionally lacked a `use` import (it resolved to a non-existent class).
  - HTTPResponseUtils::jsonErrorResponseHandler(): add the missing SystemLogger /
    SystemError / ErrorCodeStatus imports and \Throwable qualifier, drop the
    getName() call (RuntimeException has none), look the HTTP status up from the
    numeric code (not the translated name), replace the non-existent
    ErrorCode::name() with getErrorStringForErrorCode(), and wrap the JSON body in
    a stream for withBody(). ClientRestController now passes $this->logger to it.

  Non-ignorable errors (cannot be baselined):
  - Add `: array` return type to jsonSerialize() in 10 Models (Assignment and its
    subclasses, AssessmentSnippet, AssessmentSummary, Client, SystemUser) to match
    JsonSerializable's tentative return type.
  - ServerRestRequest: mark final and declare PSR-7 native return types on all
    ServerRequestInterface methods (the immutable withers return `: static`), so
    they are covariant with the interface.
  - Fill empty `: ResponseInterface` stubs that returned nothing (return.missing):
    EmptyRestController (one/create/update), AssessmentGroupRestController
    (one/update), QuestionnaireRestController (create/update),
    QuestionnaireResponseRestController (update).

  Tooling:
  - Add module-local phpstan-baseline.neon (included from phpstan.neon.dist) to
    record the remaining pre-existing style findings. It is scoped to this
    module's src/ only — it never references OpenEMR core or other modules.
    Regenerate it with `composer phpstan -- --generate-baseline <path>` in a dev
    checkout (see the file header).

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
  - Validators: add ': void' return type to configureValidator() in all 6
    validators to match 8.4.1's BaseValidator::configureValidator(): void.
  - Bootstrap: CsrfUtils::collectCsrfToken() now requires a SessionInterface —
    pass SessionWrapperFactory::getInstance()->getActiveSession() (core's idiom).
  - Cap psr/http-message to ^1.1 (core's version) so the module's PSR-7
    ServerRestRequest stays compatible — psr/http-message 2.0 added return types
    to the interfaces that the 1.x implementation does not declare.

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
