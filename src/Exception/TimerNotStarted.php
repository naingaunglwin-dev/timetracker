<?php

namespace NAL\TimeTracker\Exception;

use BadMethodCallException;

class TimerNotStarted extends BadMethodCallException
{
    public function __construct(string $id)
    {
        parent::__construct("The timer with ID '{$id}' has not been started.");
    }
}
