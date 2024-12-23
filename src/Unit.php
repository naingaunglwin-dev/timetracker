<?php

namespace NAL\TimeTracker;

use NAL\TimeTracker\Exception\DivisionByZero;
use NAL\TimeTracker\Exception\InvalidUnitName;
use NAL\TimeTracker\Exception\UnsupportedLogic;
use NAL\TimeTracker\Exception\UnsupportedOperator;

class Unit
{
    /**
     * List of supported time units.
     *
     * @var array
     */
    private array $supportedUnits = [
        'm', 's', 'ms', 'us', 'ns'
    ];

    /**
     * Definitions of unit conversions relative to seconds.
     *
     * @var array
     */
    private array $unitDefinitions = [
        'm' => [
            'operator' => '/',
            'value'    => 60
        ],
        'ms' => [
            'operator' => '*',
            'value'    => 1000
        ],
        'us' => [
            'operator' => '*',
            'value'    => 1000000
        ],
        'ns' => [
            'operator' => '*',
            'value'    => 1000000000
        ],
    ];

    /**
     * Custom units defined by the user.
     *
     * @var array
     */
    private array $customUnits = [];

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
     * @throws DivisionByZero If the value is zero.
     */
    public function add(string $unit, string $operator, int $value): void
    {
        if (empty($unit)) {
            throw new InvalidUnitName('');
        }

        if (in_array($unit, $this->getSupportedUnits())) {
            throw new InvalidUnitName($unit);
        }

        if (empty($operator) || !in_array($operator, ['+', '-', '/', '*'])) {
            throw new UnsupportedOperator($operator);
        }

        if ($value === 0) {
            throw new DivisionByZero();
        }

        $this->supportedUnits[] = $unit;

        $this->unitDefinitions[$unit] = [
            'operator' => $operator,
            'value'    => $value
        ];

        $this->customUnits[] = $unit;
    }

    /**
     * Retrieves the list of defined custom units.
     *
     * @return array An array of custom unit names.
     */
    public function getCustomUnits(): array
    {
        return $this->customUnits;
    }

    /**
     * Retrieves the list of supported units.
     *
     * @return array An array of supported unit names.
     */
    public function getSupportedUnits(): array
    {
        return $this->supportedUnits;
    }

    public function getUnitDefinitions(?string $unit = null): array
    {
        return $this->unitDefinitions[$unit] ?? [];
    }
}
