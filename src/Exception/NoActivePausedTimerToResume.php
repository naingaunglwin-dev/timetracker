<?php

namespace NAL\TimeTracker\Exception;

class NoActivePausedTimerToResume extends \BadMethodCallException
{
    public function __construct($id)
    {
        parent::__construct("The timer with ID '{$id}' has no active pause to resume");
    }
}
