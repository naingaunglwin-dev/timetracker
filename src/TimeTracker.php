<?php

namespace NAL\TimeTracker;

use Illuminate\Container\Container;
use InvalidArgumentException;
use NAL\TimeTracker\Exception\InvalidUnitName;
use NAL\TimeTracker\Exception\NoActiveTimerToStopException;
use NAL\TimeTracker\Exception\TimerNotStarted;
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
            $timeTracker->stop($randomId);
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
     */
    public function calculate(string $id): ?Result
    {
        if (!$this->exists($id)) {
            return null;
        }

        return new Result($this->unit, $this->end[$id] - $this->start[$id], 's');
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
}
