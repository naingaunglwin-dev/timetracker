# ChangeLog

## v1.1.0
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