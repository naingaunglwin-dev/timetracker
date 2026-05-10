<div align="center">

# TimeTracker

<a href="https://github.com/naingaunglwin-dev/timetracker/actions"><img src="https://github.com/naingaunglwin-dev/timetracker/actions/workflows/tests.yml/badge.svg" alt="GitHub CI Status"></a>
<a href="https://github.com/naingaunglwin-dev/dotenv/"><img src="https://img.shields.io/badge/coverage-100%25-green" alt="Code Coverage"></a>
<a href="https://github.com/naingaunglwin-dev/dotenv/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>

</div>

## Contributing
- This is an open-source library, and contributions are welcome.
- If you have any suggestions, bug reports, or feature requests, please open an issue or submit a pull request on the project repository.

## Requirement
- **PHP** version 8.3 or newer is required
- composer

## Installation via Composer
> If Composer is not installed, follow the [official guide](https://getcomposer.org/download/).

1. Create a `composer.json` file at your project root directory (if you don't have one):
```json
{
  "require": {
    "naingaunglwin-dev/timetracker": "^2.0"
  }
}
```

- Run the following command in your terminal from the project's root directory:
```bash
composer install
```

If you already have `composer.json` file in your project, just run this command in your terminal,
```bash
composer require naingaunglwin-dev/timetracker
```

## Usage
- In your php file,
```php
<?php

require 'vendor/autoload.php';

$tracker = new NAL\TimeTracker\TimeTracker();

$tracker->start('test');

echo 'hello world<br>';
sleep(3);

$tracker->stop('test');

echo $tracker->calculate('test')
        ->get();

// Output:
// hello world
// 3.0019600391388
```

### Convert to different unit
- By default, the unit is in seconds (s). You can convert to other predefined units like milliseconds (ms), microseconds (us), and more:
```php
$tracker->start('test');

echo 'hello world<br>';
sleep(3);

$tracker->stop('test');

echo $tracker->calculate('test')
        ->convert('ms')
        ->get();

// Output:
// hello world
// 3014.9321556091
```

### Add custom unit
- You can define custom units based on seconds (for example, converting seconds to custom units):
```php
$tracker->start('test');

echo 'hello world<br>';
sleep(3);

$tracker->stop('test');

// Add a custom unit definition (1 second = 10 custom units)
$tracker->addUnitDefinition('testunit', '*', 10);

echo $tracker->calculate('test')
        ->convert('testunit')
        ->get();

// Output:
// hello world
// 30.037958621979
```

### Format output
- You can format the output of the calculated time using named placeholders:
```php
$tracker->start('test');

echo 'hello world<br>';
sleep(3);

$tracker->stop('test');

echo $tracker->calculate('test')
        ->convert('ms')
        ->format('Executed in {time}{unit}') // Default: '{time} {unit}'
        ->get();

// Output:
// hello world
// Executed in 3009.4430446625ms
```

### Access raw and formatted result values
- `Result::get()` returns the raw value by default, or the formatted string after `format()` is used.
- `Result::value()` always returns the raw numeric value.
- `Result::unit()` returns the current unit.
- `Result::toArray()` returns a structured representation of the result.
```php
$result = $tracker->calculate('test')
        ->convert('ms')
        ->format('Executed in {time}{unit}');

echo $result->get();
// Executed in 3009.4430446625ms

echo $result->value();
// 3009.4430446625

echo $result->unit();
// ms

print_r($result->toArray());

// Output:
// Array
// (
//     [time] => 3009.4430446625
//     [unit] => ms
//     [formatted] => Executed in 3009.4430446625ms
// )
```

### Time tracking with callback function
- You can track time for a callback function and get both the callback result and the execution time:
```php
class Conversation
{
    public function greet($time){
        return 'good ' . $time;
    }
}

$watch = \NAL\TimeTracker\TimeTracker::watch(
    function (Conversation $conv, $time) {
        sleep(3);
        return $conv->greet($time) . '<br>do something at ' . $time;
    },
    ['time' => 'evening'] // parameters variableName => value
);

echo $watch['result'];
echo $watch['time']->convert('ms')->format('{time}{unit}')->get();
```
- Example output:
```php
array (size=2)
  'result' => string 'good evening<br>do something at evening'
  'time' => object(NAL\TimeTracker\Result)
```

### Checking timer states

The following methods help you check timer states and get currently active timers.

#### Check if a timer has started
```php
$tracker->start('download');

if ($tracker->isStarted('download')) {
    echo "Download timer is started.";
}

// Output:
// Download timer is started.
```

#### Check if a timer has stopped
```php
$tracker->start('process');

sleep(1);

$tracker->stop('process');

if ($tracker->isStopped('process')) {
    echo "Process timer is stopped.";
}

// Output:
// Process timer is stopped.
```

#### Get currently active timers
```php
$tracker->start('task1');
$tracker->start('task2');
$tracker->stop('task1');

print_r($tracker->getActiveTimers());

// Output:
// Array
// (
//     [0] => task2
// )
```

#### Check timer status
```php
$tracker->start('import');

echo $tracker->status('import');

// Output:
// in progress
```

#### Check if a completed timer exists
```php
$tracker->start('report');
$tracker->stop('report');

if ($tracker->exists('report')) {
    echo "Report timer exists.";
}

// Output:
// Report timer exists.
```

### Get all durations
- `durations()` returns completed timers converted to the requested unit. By default, it converts to milliseconds and formats each value as `{time} {unit}`.
```php
$tracker->start('task1');
usleep(10000);
$tracker->stop('task1');

$tracker->start('task2');
usleep(20000);
$tracker->stop('task2');

print_r($tracker->durations());

// Output:
// Array
// (
//     [task1] => 10.123 ms
//     [task2] => 20.456 ms
// )
```

- Pass an empty format string if you want raw numeric durations:
```php
print_r($tracker->durations('ms', ''));

// Output:
// Array
// (
//     [task1] => 10.123
//     [task2] => 20.456
// )
```

### Record laps
- Laps mark checkpoints inside a running timer.
```php
$tracker->start('build');

usleep(10000);
$tracker->lap('build', 'Dependencies installed');

usleep(20000);
$tracker->lap('build', 'Assets compiled');

$tracker->stop('build');

print_r($tracker->getLaps('build'));

// Output:
// Array
// (
//     [0] => Array
//         (
//             [description] => Dependencies installed
//             [time] => 1760000000.1234
//         )
//     [1] => Array
//         (
//             [description] => Assets compiled
//             [time] => 1760000000.5678
//         )
// )
```

### Pause and resume a timer
- Paused time is excluded from the final calculated duration.
```php
$tracker->start('download');

usleep(10000);
$tracker->pause('download', 'Waiting for network');

usleep(50000);
$tracker->resume('download', 'Network resumed');

usleep(10000);
$tracker->stop('download');

echo $tracker->calculate('download')
        ->convert('ms')
        ->format('{time} {unit}')
        ->get();
```

### Inspect a timer
- `inspect()` returns the raw tracked data for a timer, including start, end, pause, resume, lap, and status values.
```php
print_r($tracker->inspect('download'));

// Output:
// Array
// (
//     [start] => 1760000000.1234
//     [end] => 1760000000.2345
//     [paused] => Array(...)
//     [resumed] => Array(...)
//     [status] => completed
//     [laps] => Array(...)
// )
```

### Reset timers
```php
$tracker->reset('download'); // Reset one timer

$tracker->reset(); // Reset all timers
```
