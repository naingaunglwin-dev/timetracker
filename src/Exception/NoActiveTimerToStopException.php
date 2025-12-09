<?php

namespace NAL\TimeTracker\Exception;

use BadMethodCallException;

class NoActiveTimerToStopException extends BadMethodCallException
{
    public function __construct()
    {
        parent::__construct("No active timer to stop.");
    }
}
