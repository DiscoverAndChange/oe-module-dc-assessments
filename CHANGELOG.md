v0.11.0 Apply rector code modernization

  Apply rector across 64 files: constructor property promotion, readonly
  properties, null default initialization, void closure return types,
  dead code removal after return statements, and string cast fixes.
  Fix createOpenEMRSearchParameters signature in 4 FHIR service classes.
  Set up unit test suite with separate bootstrap (no database required).

v0.10.0 Add developer tooling, CLAUDE.md, and project conventions

  Add composer scripts for phpstan, rector, and phpunit that run from the OpenEMR root.
  Add CLAUDE.md with architecture docs, commands, and coding conventions.
  Establish versioning, branching, and PR conventions for AI-assisted development.

v0.9.0 Initial module release

  Release of working assessment module.
