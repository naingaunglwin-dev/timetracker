<?php

namespace NAL\TimeTracker;

use Illuminate\Container\Container;
use InvalidArgumentException;
use NAL\TimeTracker\Exception\InvalidUnitName;
use NAL\TimeTracker\Exception\NoActivePausedTimerToResume;
use NAL\TimeTracker\Exception\NoActiveTimerToStopException;
use NAL\TimeTracker\Exception\TimerAlreadyPaused;
use NAL\TimeTracker\Exception\TimerNotStarted;
use NAL\TimeTracker\Exception\UnmatchedPauseWithoutResume;
use NAL\TimeTracker\Exception\UnsupportedLogic;

class TimeTracker
{
    /**
     * Stores start times of tracked operations by ID.
     *
     * @var array
     */
    private array $start = [];

    /**
     * Stores end times of tracked operations by ID.
     *
     * @var array
     */
    private array $end = [];

    /**
     * Stores pause times of tracked operations by ID.
     *
     * @var array
     */
    private array $pause = [];

    /**
     * Stores resume times of tracked operations by ID.
     *
     * @var array
     */
    private array $resume = [];

    /**
     * Stores laps of tracked operations by ID.
     *
     * @var array
     */
    private array $laps = [];

    private Unit $unit;

    /**
     * TimeTracker Constructor
     */
    public function __construct()
    {
        $this->unit = new Unit();
    }

    /**
     * Starts a timer with the given ID.
     *
     * @param string $id The identifier for the timer.
     */
    public function start(string $id): void
    {
        $this->start[$id] = microtime(true);
    }

    /**
     * @codeCoverageIgnore
     *
     * Ends a timer with the given ID.
     *
     * @deprecated Use `stop()` instead
     *
     * @param string $id The identifier for the timer.
     *
     * @throws TimerNotStarted If the timer with the given ID has not been started.
     */
    public function end(string $id): void
    {
        if (!isset($this->start[$id])) {
            throw new TimerNotStarted($id);
        }

        $this->end[$id] = microtime(true);
    }

    /**
     * Stops the specified timer or, if no ID is provided, the most recently started timer.
     *
     * @param string|null $id  The identifier of the timer to stop, or null to stop the last started timer.
     *
     * @throws TimerNotStarted  If no timer has been started, or the specified timer ID does not exist.
     * @throws \RuntimeException If the specified timer has already been stopped.
     *
     * @return void
     */
    public function stop(?string $id = null): void
    {
        if (empty($this->start)) {
            throw new TimerNotStarted($id);
        }

        if ($id === null) {
            $id = array_key_last($this->start);
        }

        if (!isset($this->start[$id])) {
            throw new TimerNotStarted($id);
        }

        if (isset($this->end[$id])) {
            throw new NoActiveTimerToStopException();
        }

        $this->end[$id] = microtime(true);
    }

    /**
     * Records a lap time for the specified timer.
     *
     * A lap represents a checkpoint within an ongoing timer session. Each lap is recorded
     * with its timestamp and an optional description for context.
     *
     * @param string $id           The identifier of the timer.
     * @param string $description  An optional label or note for this lap (default: empty string).
     *
     * @throws TimerNotStarted If the timer with the given ID has not been started.
     *
     * @return void
     */
    public function lap(string $id, string $description = ''): void
    {
        if (!isset($this->start[$id])) {
            throw new TimerNotStarted($id);
        }

        $this->laps[$id][] = [
            'description' => $description,
            'time' => microtime(true)
        ];
    }

    /**
     * Retrieves all recorded laps for a given timer.
     *
     * Each lap contains its timestamp and optional description.
     * If no laps have been recorded, an empty array is returned.
     *
     * @param string $id  The identifier of the timer.
     *
     * @return array<int, array{description: string, time: float}>  A list of laps with their details.
     */
    public function getLaps(string $id): array
    {
        return $this->laps[$id] ?? [];
    }

    /**
     * Pauses the specified timer.
     *
     * If the timer is already paused and not yet resumed, this method throws an exception.
     * Each pause event is recorded with a timestamp and an optional description.
     *
     * @param string $id           The identifier of the timer.
     * @param string $description  An optional label or note for this pause (default: empty string).
     *
     * @throws TimerNotStarted If the timer with the given ID has not been started.
     * @throws TimerAlreadyPaused If the timer is already paused.
     *
     * @return void
     */
    public function pause(string $id, string $description = ''): void
    {
        if (!isset($this->start[$id])) {
            throw new TimerNotStarted($id);
        }

        if (isset($this->pause[$id]) && count($this->pause[$id]) > count($this->resume[$id] ?? [])) {
            throw new TimerAlreadyPaused($id);
        }

        $this->pause[$id][] = [
            'description' => $description,
            'time' => microtime(true)
        ];
    }

    /**
     * Resumes a previously paused timer.
     *
     * A timer can only be resumed if it has an active pause entry that has not yet been resumed.
     * Each resume event is recorded with a timestamp and an optional description.
     *
     * @param string $id           The identifier of the timer.
     * @param string $description  An optional label or note for this resume (default: empty string).
     *
     * @throws TimerNotStarted If the timer with the given ID has not been started.
     * @throws NoActivePausedTimerToResume If there is no active pause to resume.
     *
     * @return void
     */
    public function resume(string $id, string $description = ''): void
    {
        if (!isset($this->start[$id])) {
            throw new TimerNotStarted($id);
        }

        if (!isset($this->pause[$id]) || count($this->pause[$id]) === count($this->resume[$id] ?? [])) {
            throw new NoActivePausedTimerToResume($id);
        }

        $this->resume[$id][] = [
            'description' => $description,
            'time' => microtime(true)
        ];
    }

    /**
     * Check whether timer with given id is started or not
     *
     * @param string $id
     *
     * @return bool
     */
    public function isStarted(string $id): bool
    {
        return array_key_exists($id, $this->start);
    }

    /**
     * Check whether timer with given id is ended or not
     *
     * @param string $id
     *
     * @return bool
     */
    public function isStopped(string $id): bool
    {
        return array_key_exists($id, $this->end);
    }

    /**
     * Get the current active timers' id
     *
     * @return array
     */
    public function getActiveTimers(): array
    {
        if ($this->start === []) {
            return [];
        }

        $ids = array_keys($this->start);
        $activeTimers = [];

        foreach ($ids as $id) {
            if (!$this->isStopped($id)) {
                $activeTimers[] = $id;
            }
        }

        return $activeTimers;
    }

    /**
     * @codeCoverageIgnore
     *
     * Executes a callback while tracking its execution time.
     *
     * @deprecated Use `watch` instead
     *
     * @param callable $callback The callback function to execute.
     * @param array $params Parameters to pass to the callback.
     * @param string $unit The unit for measuring execution time.
     * @return array{result: Result, time: float|int, unit: string, output: mixed} An array containing Result, the execution time, unit, and callback result.
     */
    public static function run(callable $callback, array $params = [], string $unit = 's'): array
    {
        $timeTracker = new self();

        $randomId = bin2hex(random_bytes(16));

        $container = new Container();

        $timeTracker->start($randomId);

        try {

            $output = $container->call($callback, $params);

        } catch (\Throwable $e) {
            $timeTracker->stop($randomId);

            throw new \RuntimeException(
                $timeTracker->calculate($randomId)->format('Error occurring during executing callback, end in %s%s')->get() .
                "\n{$e->getMessage()}",
                $e->getCode(),
                $e
            );
        } finally {
            if (!$timeTracker->isStopped($randomId)) {
                $timeTracker->stop($randomId);
            }
        }

        $result = $timeTracker->calculate($randomId);

        return [
            'result' => $result,
            'time'   => $result->convert($unit)->get(),
            'unit'   => $unit,
            'output' => $output ?? null
        ];
    }

    /**
     * Executes a callback while tracking its execution time.
     *
     * @param callable $callback The callback function to execute.
     * @param array $params Parameters to pass to the callback.
     * @param string $unit The unit for measuring execution time.
     * @return array{result: Result, time: float|int, unit: string, output: mixed} An array containing Result, the execution time, unit, and callback result.
     */
    public static function watch(callable $callback, array $params = [], string $unit = 's'): array
    {
        $timeTracker = new self();

        $randomId = bin2hex(random_bytes(16));

        $container = new Container();

        $timeTracker->start($randomId);

        try {

            $output = $container->call($callback, $params);

        } catch (\Throwable $e) {
            $timeTracker->stop($randomId);

            throw new \RuntimeException(
                $timeTracker->calculate($randomId)->format('Error occurring during executing callback, end in %s%s')->get() .
                "\n{$e->getMessage()}",
                $e->getCode(),
                $e
            );
        } finally {
            if (!$timeTracker->isStopped($randomId)) {
                $timeTracker->stop($randomId);
            }
        }

        $result = $timeTracker->calculate($randomId);

        return [
            'result' => $result,
            'time'   => $result->convert($unit)->get(),
            'unit'   => $unit,
            'output' => $output ?? null
        ];
    }

    /**
     * Calculates the elapsed time for a timer.
     *
     * @param string $id The identifier for the timer.
     * @return Result|null The Result instance or null if the timer does not exist.
     *
     * @throws UnmatchedPauseWithoutResume
     */
    public function calculate(string $id): ?Result
    {
        if (!$this->exists($id)) {
            return null;
        }

        $calculate = $this->end[$id] - $this->start[$id];

        $pausedTime = 0;

        if (!empty($this->pause[$id]) && !empty($this->resume[$id])) {
            foreach ($this->pause[$id] as $index => $pauseTime) {
                if (isset($this->resume[$id][$index])) {
                    $pausedTime += $this->resume[$id][$index]['time'] - $pauseTime['time'];
                } else {
                    throw new UnmatchedPauseWithoutResume($id);
                }
            }
        }

        $calculate -= $pausedTime;

        return new Result($this->unit, $calculate, 's');
    }


    /**
     * Adds a custom unit definition based on seconds.
     *
     * <b>
     * The `$value` parameter represents a conversion factor based on seconds. For example,
     * if you're adding a millisecond unit, the `$value` should be 1000 (since 1 second = 1000 milliseconds).
     * </b>
     *
     * @param string $unit The name of the custom unit.
     * @param string $operator The mathematical operator for conversion. Supported operators: '+', '-', '/', '*'.
     * @param int $value The conversion value, based on seconds. For example, 1000 for milliseconds (1 second = 1000 milliseconds).
     * @throws InvalidUnitName If the unit name is invalid or already exists.
     * @throws UnsupportedLogic If the operator is unsupported.
     * @throws InvalidArgumentException If the value is zero or the operator is invalid.
     */
    public function addUnitDefinition(string $unit, string $operator, int $value): void
    {
        $this->unit->add($unit, $operator, $value);
    }

    /**
     * Resets the tracked timers.
     *
     * @param string|null $id The identifier for the timer to reset. If null, all timers are reset.
     */
    public function reset(?string $id = null): void
    {
        if ($id === null) {
            $this->start = [];
            $this->end = [];
        } else {
            unset($this->start[$id], $this->end[$id]);
        }
    }

    /**
     * Returns the current status of a timer.
     *
     * @param string $id The identifier for the timer.
     * @return string The current status (either 'not started', 'in progress', or 'completed').
     */
    public function status(string $id): string
    {
        if (!isset($this->start[$id])) {
            return TimerStatus::NOT_STARTED->value;
        }

        if (!isset($this->end[$id])) {
            return TimerStatus::IN_PROGRESS->value;
        }

        return TimerStatus::COMPLETED->value;
    }

    /**
     * Checks if the timer with the given ID exists.
     *
     * @param string $id The identifier for the timer.
     * @return bool True if the timer exists, false otherwise.
     */
    public function exists(string $id): bool
    {
        return isset($this->start[$id]) && isset($this->end[$id]);
    }

    /**
     * Returns an array of durations for all tracked timers.
     *
     * @param string $unit The unit for the duration (default is 'ms').
     * @param string $format The format string for the result (default is '%s %s').
     * @return array An array where the keys are timer IDs and the values are the calculated durations.
     */
    public function durations(string $unit = 'ms', string $format = '%s %s'): array
    {
        $result = [];
        foreach ($this->end as $id => $time) {
            $calculate = $this->calculate($id)->convert($unit);

            if (!empty($format)) {
                $calculate->format($format);
            }

            $result[$id] = $calculate->get();
        }

        return $result;
    }

    public function getUnit(): Unit
    {
        return $this->unit;
    }

    /**
     * Inspects and retrieves detailed timing data for a specific timer.
     *
     * @param string $id  The identifier of the timer to inspect.
     *
     * @return array{
     *     start: float|null,
     *     end: float|null,
     *     paused: array<int, array{description: string, time: float}>,
     *     resumed: array<int, array{description: string, time: float}>,
     *     status: string,
     *     laps: array<int, array{description: string, time: float}>
     * }  A structured array containing all tracked data for the specified timer.
     */
    public function inspect(string $id): array
    {
        return [
            'start'   => $this->start[$id] ?? null,
            'end'     => $this->end[$id] ?? null,
            'paused'  => $this->pause[$id] ?? [],
            'resumed' => $this->resume[$id] ?? [],
            'status'  => $this->status($id),
            'laps'    => $this->laps[$id] ?? []
        ];
    }
}
