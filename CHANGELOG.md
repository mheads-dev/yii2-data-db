# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-09-17

### Added

- Initial Yii2 query adapter for `yiisoft/data`.
- `QueryDataReader` with filtering, sorting, limits, offsets, counting, HAVING, and batch reading.
- `QueryDataReaderInterface`.
- `QueryFilterCompiler` for converting `yiisoft/data` filters to Yii2 query conditions.
- `ConditionFilter` for passing existing Yii2 conditions as filters.
- Unit tests and MySQL integration tests, including `yii\db\ActiveQuery` coverage.
- English and Russian README files.
