<div align="center">

# TimeTracker

![Status](https://img.shields.io/badge/test-pass-green)
![Status](https://img.shields.io/badge/coverage-100%25-green)
![License](https://img.shields.io/badge/license-MIT-blue.svg)

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
    "naingaunglwin-dev/timetracker": "^1.0"
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
- You can format the output of the calculated time using placeholders:
```php
$tracker->start('test');

echo 'hello world<br>';
sleep(3);

$tracker->stop('test');

echo $tracker->calculate('test')
        ->convert('ms')
        ->format('Executed at %s%s') // Change format to suit your needs (default: '%s %s')
        ->get();

// Output:
// hello world
// Executed at 3009.4430446625ms
```

### Time tracking with callback function
- You can track time for a callback function and get both the execution time and the result:
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
    ['time' => 'evening'], //parameters variableName => value
    'ms' // time unit, default is `s`
);
```
- Example output:
```php
array (size=4)
  'result' => 
    object(NAL\TimeTracker\Result)[39]
      ...
  'time' => float 3002.8040409088
  'unit' => string 'ms' (length=2)
  'output' => string 'good evening, do something at evening' (length=37)
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
