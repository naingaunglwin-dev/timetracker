<?php

namespace NAL\TimeTracker;

use NAL\TimeTracker\Exception\DivisionByZero;
use NAL\TimeTracker\Exception\UnknownUnit;
use NAL\TimeTracker\Exception\UnsupportedLogic;

class Result
{
    /**
     * Result Constructor
     *
     * @param Unit $unit The unit system used for calculations.
     * @param float|int|string|null $calculated The initial calculated time value. It can be:
     *   - `float` or `int` for numerical results,
     *   - `string` for formatted or raw values,
     *   - `null` if the value is not yet calculated.
     * @param string $lastUpdatedUnit The unit associated with the calculated value, e.g., 's' for seconds.
     */
    public function __construct(
        private readonly Unit $unit,
        private readonly null|float|int|string $calculated,
        private string $lastUpdatedUnit
    )
    {
    }

    /**
     * Formats the calculated time.
     *
     * @param string $format The format string, where `%s` is replaced with the time and unit.
     * @return Result
     */
    public function format(string $format = '%s %s'): Result
    {
        return new self($this->unit, sprintf($format, $this->calculated, $this->lastUpdatedUnit), $this->lastUpdatedUnit);
    }

    /**
     * Retrieves the calculated time.
     *
     * @return float|int|string|null The calculated time.
     */
    public function get(): float|int|string|null
    {
        return $this->calculated;
    }

    /**
     * Converts the calculated time to a different unit.
     *
     * @param string $unit The target unit.
     * @return Result
     * @throws UnknownUnit If the unit is not supported.
     * @throws DivisionByZero If a division by zero occurs during conversion.
     * @throws UnsupportedLogic If the unit definition contains an unsupported operator.
     */
    public function convert(string $unit): Result
    {
        if (!in_array($unit, $this->unit->getSupportedUnits())) {
            throw new UnknownUnit($unit, $this->unit->getSupportedUnits());
        }

        $calculated = $this->calculated;

        if ($this->calculated !== null) {

            if ($this->lastUpdatedUnit !== 's') {
                $calculated = $this->convertToSecond();
                $this->lastUpdatedUnit = 's';
            }

            if ($unit !== 's') {
                $definition = $this->unit->getUnitDefinitions($unit);

                $calculated = $this->_convert($calculated, $definition['value'], $definition['operator']);
            }
        }

        return new self($this->unit, $calculated, $unit);
    }

    /**
     * Converts the calculated time to seconds based on the current unit.
     *
     * This adjusts the calculated time from the current unit to seconds using
     * the conversion factor defined in the unitDefinitions array. It modifies the
     * `calculated` property and sets the `lastUpdatedUnit` to 's' (seconds).
     *
     * @throws UnsupportedLogic If the operator defined for the current unit is not supported.
     */
    private function convertToSecond(): null|string|float|int
    {
        $definition = $this->unit->getUnitDefinitions($this->lastUpdatedUnit);

        return $this->_convert($this->calculated, $definition['value'], $definition['operator'], true);
    }

    /**
     * Performs a mathematical conversion based on the given operator.
     *
     * @param float|int $from The initial value.
     * @param float|int $to The conversion factor.
     * @param string $operator The operator to apply (+, -, *, /).
     * @param bool $reverse Whether to reverse the operation for "to seconds" conversion.
     * @return float|int The converted value.
     * @throws DivisionByZero If a division by zero occurs.
     * @throws UnsupportedLogic If the operator is unsupported.
     */
    private function _convert(float|int $from, float|int $to, string $operator, bool $reverse = false): float|int
    {
        return match ($operator) {
            '+' => $reverse ? $from - $to : $from + $to,
            '-' => $reverse ? $from + $to : $from - $to,
            '*' => $reverse ? $from / $to : $from * $to,
            '/' => $reverse ? $from * $to : $from / $to,
            default => throw new UnsupportedLogic("Unsupported operator '{$operator}' in unit definition."),
        };
    }
}
