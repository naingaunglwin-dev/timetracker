<?php

namespace NAL\TimeTracker\Exception;

use LogicException;

class UnsupportedLogic extends LogicException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
