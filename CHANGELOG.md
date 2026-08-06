# Changelog

All notable changes to this project are documented in this file.

## [4.7.0] - Unreleased

### Security

- Bind search input in every SQLite case-sensitive `GLOB` mode. Previous releases interpolated the value into raw SQL,
  which allowed SQL injection through all five `*_CASE_SENSITIVE` search modes.

### Added

- Add `searchLiteral()` for searches where SQL `LIKE` and SQLite `GLOB` wildcard characters are ordinary input.
- Add the opt-in `search_wildcards_as_literals` setting for existing `search()` and query-string calls.
- Add optional character and term limits with configurable empty-result or `SearchInputException` behavior.

### Changed

- Normalize Unicode case consistently for MySQL and PostgreSQL searches.
- Provide Unicode-aware single-character case matching for SQLite searches.
- Split `CONTAINS_ANY` and `CONTAINS_ALL` terms on normalized whitespace, including tabs and newlines.

### Fixed

- Reject invalid `search_limit_exceeded_behavior` values instead of silently returning an empty result.
- Preserve PostgreSQL's `::text` cast for `LIKE` searches on non-text columns.
- Quote and qualify searched columns consistently across database grammars.
