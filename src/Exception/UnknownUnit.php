<?php

namespace NAL\TimeTracker\Exception;

use InvalidArgumentException;

class UnknownUnit extends InvalidArgumentException
{
    public function __construct($unit, array $supported)
    {
        parent::__construct('Unsupported unit: ' . $unit . '. Use' . implode(', ', array_map(fn ($v) => "'$v'", $supported)) . 'instead.');
    }
}
