# ChangeLog

## v2.0.0 - 2026-04-25
### Added
- Added `Result::value()` and `Result::unit()` to access the raw calculated value and current unit separately from formatted output.
- Added `Result::toArray()` for structured result output with raw `time`, `unit`, and optional `formatted` values.

### Changed
- Changed `Result::format()` from positional placeholders like `%s %s` / `%s%s` to named placeholders like `{time}` and `{unit}` for clearer, more extensible formatting templates.
- Updated `Result` to keep the raw calculated value separate from the selected format template. `Result::get()` still returns the rendered string when a format is set, while the raw value remains available through `Result::value()`.
- Changed `TimeTracker::watch()` to return the callback result in `result` and the execution time as a `Result` instance in `time`.

### Fixed
- Fixed formatted `TimeTracker::durations()` output by preserving the immutable `Result` returned from `Result::format()`.
- Updated callback error formatting in `TimeTracker::watch()` to use the named `{time}` and `{unit}` placeholders.

### Breaking Changes
- Existing format templates that use `%s` placeholders must be updated to use `{time}` and `{unit}` instead.
- The `Result` constructor now expects the calculated value to be raw `float|int|null`; formatted strings should be produced through `Result::format()`.
- `TimeTracker::watch()` no longer returns separate `unit` and `output` keys. Use `time` for the `Result` instance and `result` for the callback return value.

## v1.1.0 - 2025-12-09
### Added
- Implemented `__toString()` method for the `Result` class, allowing instances to be converted to strings (e.g. `"$result"` now returns the calculated value). (PR #1)
- Added new `stop()` method to the timer, supporting stopping: (PR #2)
    - a specific timer by ID
    - **or** the **most recently started timer** when no ID is provided.
- Added new `watch()` static method to replace `run()` for executing and timing callbacks. (PR #4)
- Added new timer utility methods `isStarted()`, `isStopped()`, and `getActiveTimers()` in `TimeTracker` to inspect active and completed timers. (PR #7)
- Added new timer utility methods `lap()`, `getLaps()`, `pause()`, `resume()`, and `inspect()` in `TimeTracker`. (PR #9)

### Changed
- Replaced `ramsey/uuid` with native PHP functions (`bin2hex(random_bytes(16))`) for generating random IDs. (PR #3)
- Removed the `ramsey/uuid` dependency as it is no longer required. (PR #3)
- Replaced `STATUS_*` string constants with a dedicated `TimerStatus` enum. (PR #5)

### Fixed
- Prevent duplicate `stop()` calls in `watch()` by adding an `isStopped()` check in the `finally` block. (RP #8)

### Deprecated
- Marked `end()` as deprecated. It still works for backward compatibility but will be removed in a future major release. (PR #2)
- Marked `run()` as deprecated. It still works for backward compatibility but will be removed in a future major release. (PR #4)
