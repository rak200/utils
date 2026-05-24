# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.2] - 2026-05-23

### Added

- `docs/` folder: one markdown file per class with a runnable example for every public method (`bare` and `*OrNull` variants documented together). Index in `docs/README.md`.
- README link to the new reference docs.

### Changed

- `.gitattributes`: `/docs export-ignore` so the new docs ship with the GitHub repo but stay out of the `composer archive` tarball.

## [0.1.1] - 2026-05-23

### Added

- PHPDoc on every class and public method (class summary, parameter / return / throws notes where useful).
- `@author rak200 <rak.ricardo@windowslive.com>` on every class.
- `.gitattributes` with `export-ignore` rules so `composer archive` / dist tarballs skip dev files (`tests/`, `phpunit.xml`, CI/dotfiles, `CLAUDE.md`) and force LF line endings on text files.

## [0.1.0] - 2026-05-23

### Added

- Initial release.
- Tier 1 classes: `Str`, `Arr`, `Num`, `Rand`.
- Tier 2 classes: `Regex`, `Hash`, `Bit`, `File`, `Json`, `Base64`, `Dt`.
- Alphabet constants on `Rand`: `NUM`, `HEX`, `ALPHA`, `ALNUM`.
- UUID v4, UUID v7, ULID (Crockford base32, bit-stream encoded) and nanoid generators on `Rand`.

[0.1.2]: https://github.com/rak200/utils/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/rak200/utils/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/rak200/utils/releases/tag/0.1.0
