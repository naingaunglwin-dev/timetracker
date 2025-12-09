<?php

namespace NAL\TimeTracker\Exception;

use RuntimeException;

class UnmatchedPauseWithoutResume extends RuntimeException
{
    public function __construct($id)
    {
        parent::__construct("Unmatched pause without resume for timer with ID '{$id}'");
    }
}
