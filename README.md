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
To install, navigate to your project root directory (where `composer.json` is located) and run the following command:
```shell
  composer require naingaunglwin-dev/timetracker
```
- If `composer.json` doesn't exits, run this command first,
```shell
  composer init
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

$tracker->end('test');

echo $tracker->calculate('test')
        ->get();

// Output:
// hello world
// 3.0019600391388
```

### Convert to different unit
- By default, the unit is in seconds (s). You can convert to other predefined units like milliseconds (ms), microseconds (us), and more:
```php
<?php

require 'vendor/autoload.php';

$tracker = new NAL\TimeTracker\TimeTracker();

$tracker->start('test');

echo 'hello world<br>';
sleep(3);

$tracker->end('test');

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
<?php

require 'vendor/autoload.php';

$tracker = new NAL\TimeTracker\TimeTracker();

$tracker->start('test');

echo 'hello world<br>';
sleep(3);

$tracker->end('test');

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
<?php

require 'vendor/autoload.php';

$tracker = new NAL\TimeTracker\TimeTracker();

$tracker->start('test');

echo 'hello world<br>';
sleep(3);

$tracker->end('test');

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
$result = \NAL\TimeTracker\TimeTracker::run(
    function (Conversation $conv, $time) {
        sleep(3);
        return $conv->greet($time) . '<br>do something at ' . $time;
    },
    ['time' => 'evening'], //parameters variableName => value
    'ms' // time unit, default is `s`
);

class Conversation
{
    public function greet($time){
        return 'good ' . $time;
    }
}

var_dump($result);
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
