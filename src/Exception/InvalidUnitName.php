<?php

namespace NAL\TimeTracker\Exception;

use InvalidArgumentException;

class InvalidUnitName extends InvalidArgumentException
{
    public function __construct(string $unit)
    {
        parent::__construct(
            empty($unit)
                ? "The unit name cannot be empty."
                : "The unit name '{$unit}' is invalid or already exists."
        );
    }
}
