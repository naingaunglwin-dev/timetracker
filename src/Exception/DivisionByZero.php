<?php

namespace NAL\TimeTracker\Exception;

use InvalidArgumentException;

class DivisionByZero extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct("Division by zero in unit conversion.");
    }
}
