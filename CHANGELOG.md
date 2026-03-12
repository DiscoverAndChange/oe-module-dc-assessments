v0.11.0 Fix unit tests and FHIR service method signature mismatches

  Separate unit and integration test suites with dedicated bootstraps.
  Fix LibraryAssetFHIRResourceServiceTest extending wrong TestCase class.
  Fix createOpenEMRSearchParameters signature in 4 FHIR service classes to
  match parent ResourceServiceSearchTrait.

v0.10.0 Add developer tooling, CLAUDE.md, and project conventions

  Add composer scripts for phpstan, rector, and phpunit that run from the OpenEMR root.
  Add CLAUDE.md with architecture docs, commands, and coding conventions.
  Establish versioning, branching, and PR conventions for AI-assisted development.

v0.9.0 Initial module release

  Release of working assessment module.
