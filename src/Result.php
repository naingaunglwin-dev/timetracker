<?php

namespace NAL\TimeTracker;

use NAL\TimeTracker\Exception\DivisionByZero;
use NAL\TimeTracker\Exception\UnknownUnit;
use NAL\TimeTracker\Exception\UnsupportedLogic;

final class Result
{
    /**
     * Result Constructor
     *
     * @param Unit $unit The unit system used for calculations.
     * @param float|int|null $calculated The raw calculated time value.
     * @param string $lastUpdatedUnit The unit associated with the calculated value, e.g., 's' for seconds.
     * @param string|null $format The optional display format template.
     */
    public function __construct(
        private readonly Unit $unit,
        private readonly null|float|int $calculated,
        private readonly string $lastUpdatedUnit,
        private readonly ?string $format = null
    )
    {
    }

    /**
     * Formats the calculated time using a named-placeholder template.
     *
     * Supported placeholders:
     * - `{time}`: the calculated value
     * - `{unit}`: the current unit
     *
     * @param string $format The template string. Defaults to `{time} {unit}`.
     * @return Result A new result instance containing the selected display format.
     */
    public function format(string $format = '{time} {unit}'): Result
    {
        return new self($this->unit, $this->calculated, $this->lastUpdatedUnit, $format);
    }

    /**
     * Retrieves the calculated time.
     *
     * @return float|int|string|null The raw calculated time, or the formatted value when a format is set.
     */
    public function get(): float|int|string|null
    {
        if ($this->format !== null && $this->calculated !== null) {
            return $this->render($this->format);
        }

        return $this->calculated;
    }

    /**
     * Retrieves the raw calculated time without applying formatting.
     *
     * @return float|int|null The raw calculated time.
     */
    public function value(): float|int|null
    {
        return $this->calculated;
    }

    /**
     * Retrieves the current unit for the calculated time.
     *
     * @return string The current unit.
     */
    public function unit(): string
    {
        return $this->lastUpdatedUnit;
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
        if (!in_array($unit, $this->unit->getSupportedUnits(), true)) {
            throw new UnknownUnit($unit, $this->unit->getSupportedUnits());
        }

        $calculated = $this->calculated;
        $lastUpdatedUnit = $this->lastUpdatedUnit;

        if ($this->calculated !== null) {

            if ($lastUpdatedUnit !== 's') {
                $calculated = $this->convertToSecond($calculated, $lastUpdatedUnit);
            }

            if ($unit !== 's') {
                $definition = $this->unit->getUnitDefinitions($unit);

                $calculated = $this->_convert($calculated, $definition['value'], $definition['operator']);
            }
        }

        return new self($this->unit, $calculated, $unit, $this->format);
    }

    /**
     * Converts the calculated time to seconds based on the current unit.
     *
     * This adjusts the calculated time from the current unit to seconds using
     * the conversion factor defined in the unitDefinitions array.
     *
     * @param float|int $calculated The calculated value to convert.
     * @param string $unit The current unit of the calculated value.
     * @return float|int The calculated value converted to seconds.
     * @throws UnsupportedLogic If the operator defined for the current unit is not supported.
     */
    private function convertToSecond(float|int $calculated, string $unit): float|int
    {
        $definition = $this->unit->getUnitDefinitions($unit);

        return $this->_convert($calculated, $definition['value'], $definition['operator'], true);
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->get();
    }

    /**
     * Converts the result into a structured array.
     *
     * @return array{time: float|int|null, unit: string, formatted: string|null}
     */
    public function toArray(): array
    {
        return [
            'time'      => $this->calculated,
            'unit'      => $this->lastUpdatedUnit,
            'formatted' => $this->format !== null && $this->calculated !== null
                ? $this->render($this->format)
                : null,
        ];
    }

    /**
     * Renders the calculated time with the given named-placeholder template.
     *
     * @param string $format The display format template.
     * @return string The rendered result.
     */
    private function render(string $format): string
    {
        return strtr($format, [
            '{time}' => (string) $this->calculated,
            '{unit}' => $this->lastUpdatedUnit,
        ]);
    }
}
