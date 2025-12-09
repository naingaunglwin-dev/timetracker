<?php

namespace NAL\TimeTracker\Exception;

use BadMethodCallException;

class TimerAlreadyPaused extends BadMethodCallException
{
    public function __construct($id)
    {
        parent::__construct("The timer with ID '{$id}' is already paused and not yet resumed");
    }
}
