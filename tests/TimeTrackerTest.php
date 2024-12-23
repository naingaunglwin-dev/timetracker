<?php

use NAL\TimeTracker\Exception\TimerNotStarted;
use PHPUnit\Framework\TestCase;
use NAL\TimeTracker\TimeTracker;
use NAL\TimeTracker\Result;
use NAL\TimeTracker\Unit;
use NAL\TimeTracker\Exception\DivisionByZero;
use NAL\TimeTracker\Exception\InvalidUnitName;
use NAL\TimeTracker\Exception\UnknownUnit;
use NAL\TimeTracker\Exception\UnsupportedLogic;

class TimeTrackerTest extends TestCase
{
    public function testStartAndEndTimer(): void
    {
        $tracker = new TimeTracker();
        $id = 'test_timer';

        $tracker->start($id);
        usleep(50000); // 50ms delay
        $tracker->end($id);

        $result = $tracker->calculate($id);

        $this->assertNotNull($result);
        $this->assertGreaterThan(0, $result->get());
    }

    public function testEndWithInvalidId()
    {
        $this->expectException(TimerNotStarted::class);
        $tracker = new TimeTracker();

        $tracker->end('invalid_timer');
    }

    public function testCalculateWithInvalidId()
    {
        $tracker = new TimeTracker();

        $result = $tracker->calculate('invalid_timer');

        $this->assertNull($result);
    }

    public function testStatus(): void
    {
        $tracker = new TimeTracker();
        $id = 'test_timer';

        $this->assertSame(TimeTracker::STATUS_NOT_STARTED, $tracker->status($id));

        $tracker->start($id);
        $this->assertSame(TimeTracker::STATUS_IN_PROGRESS, $tracker->status($id));

        $tracker->end($id);
        $this->assertSame(TimeTracker::STATUS_COMPLETED, $tracker->status($id));
    }

    public function testRun(): void
    {
        $result = TimeTracker::run(function () {
            usleep(50000); // 50ms delay
        });

        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('time', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('output', $result);
        $this->assertGreaterThan(0, $result['time']);
    }

    public function testRunWithExceptionThrow()
    {
        $this->expectException(RuntimeException::class);

        $result = TimeTracker::run(function () {
            usleep(50000); // 50ms delay
            invalidFunction();
        });
    }

    public function testAddUnitDefinition(): void
    {
        $tracker = new TimeTracker();
        $tracker->addUnitDefinition('custom_unit', '*', 2000);

        $unit = $tracker->getUnit();

        $this->assertContains('custom_unit', $unit->getSupportedUnits());
    }

    public function testInvalidUnitName(): void
    {
        $this->expectException(InvalidUnitName::class);

        $tracker = new TimeTracker();
        $tracker->addUnitDefinition('', '*', 1000);
    }

    public function testDivisionByZero(): void
    {
        $this->expectException(DivisionByZero::class);

        $tracker = new TimeTracker();
        $tracker->addUnitDefinition('zero_unit', '/', 0);
    }

    public function testUnsupportedLogic(): void
    {
        $this->expectException(UnsupportedLogic::class);

        $tracker = new TimeTracker();
        $tracker->addUnitDefinition('invalid_op', '^', 1000);
    }

    public function testDurations(): void
    {
        $tracker = new TimeTracker();
        $id1 = 'timer1';
        $id2 = 'timer2';

        $tracker->start($id1);
        usleep(10000); // 10ms delay
        $tracker->end($id1);

        $tracker->start($id2);
        usleep(20000); // 20ms delay
        $tracker->end($id2);

        $durations = $tracker->durations();

        $this->assertCount(2, $durations);
        $this->assertArrayHasKey($id1, $durations);
        $this->assertArrayHasKey($id2, $durations);
    }

    public function testResetFunctionality(): void
    {
        $timeTracker = new TimeTracker();

        // Start multiple timers
        $timeTracker->start('timer1');
        $timeTracker->start('timer2');
        $timeTracker->end('timer1');
        $timeTracker->end('timer2');

        // Verify timers exist
        $this->assertTrue($timeTracker->exists('timer1'));
        $this->assertTrue($timeTracker->exists('timer2'));

        // Reset a specific timer
        $timeTracker->reset('timer1');
        $this->assertFalse($timeTracker->exists('timer1'));
        $this->assertTrue($timeTracker->exists('timer2'));

        // Reset all timers
        $timeTracker->reset();
        $this->assertFalse($timeTracker->exists('timer2'));
    }

    // Result class
    public function testFormat(): void
    {
        $unit = new Unit();
        $result = new Result($unit, 123.456, 'ms');

        $formatted = $result->format('%s %s');

        $this->assertSame('123.456 ms', $formatted->get());
    }

    public function testConvert(): void
    {
        $unit = new Unit();
        $result = new Result($unit, 1, 's');

        $converted = $result->convert('ms');

        $this->assertSame(1000, $converted->get());
    }

    public function testUnknownUnit(): void
    {
        $this->expectException(UnknownUnit::class);

        $unit = new Unit();
        $result = new Result($unit, 1, 's');
        $result->convert('unknown_unit');
    }

    /**
     * Test for reversion, that unit is always convert back to second whenever user convert to another unit
     *
     * @return void
     */
    public function testConvertOneUnitToAnotherUnit()
    {
        $unit = new Unit();
        $result = new Result($unit, 1, 's');

        $convertedDivision = $result->convert('ms')->convert('us');

        $convertedMultiply = $result->convert('m');

        $unit->add('minus', '-', '10');
        $unit->add('plus', '+', '10');

        $convertedPlus = $result->convert('minus');
        $convertedMinus = $result->convert('plus');

        $this->assertSame(1000000, $convertedDivision->get());
        $this->assertSame(1 / 60, $convertedMultiply->get());
        $this->assertSame(-9, $convertedPlus->get());
        $this->assertSame(11, $convertedMinus->get());
    }

    //Unit class
    public function testAddCustomUnit(): void
    {
        $unit = new Unit();
        $unit->add('custom', '*', 500);

        $this->assertContains('custom', $unit->getSupportedUnits());
    }

    public function testAddCustomUnitWhichAlreadyExist()
    {
        $this->expectException(InvalidUnitName::class);

        $unit = new Unit();
        $unit->add('ms', '*', 1000);
    }

    public function testGetCustomUnits(): void
    {
        $unit = new Unit();
        $unit->add('custom', '*', 500);
        $customUnits = $unit->getCustomUnits();

        $this->assertSame(['custom'], $customUnits);
    }

    public function testGetUnitDefinitions(): void
    {
        $unit = new Unit();

        $definition = $unit->getUnitDefinitions('ms');

        $this->assertSame(['operator' => '*', 'value' => 1000], $definition);
    }
}
