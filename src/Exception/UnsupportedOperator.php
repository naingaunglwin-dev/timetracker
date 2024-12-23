<?php

namespace NAL\TimeTracker\Exception;

class UnsupportedOperator extends UnsupportedLogic
{
    public function __construct(string $operator)
    {
        parent::__construct("The operator '{$operator}' is unsupported. Supported operators are '+', '-', '*', '/'.");
    }
}
