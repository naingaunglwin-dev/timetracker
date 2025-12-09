<?php

namespace NAL\TimeTracker;

enum TimerStatus: string
{
    case NOT_STARTED = 'not started';
    case IN_PROGRESS = 'in progress';
    case COMPLETED   = 'completed';
}
