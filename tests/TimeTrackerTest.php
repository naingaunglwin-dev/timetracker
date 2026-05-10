<?php

use NAL\TimeTracker\Exception\DivisionByZero;
use NAL\TimeTracker\Exception\InvalidUnitName;
use NAL\TimeTracker\Exception\NoActivePausedTimerToResume;
use NAL\TimeTracker\Exception\NoActiveTimerToStopException;
use NAL\TimeTracker\Exception\TimerAlreadyPaused;
use NAL\TimeTracker\Exception\TimerNotStarted;
use NAL\TimeTracker\Exception\UnmatchedPauseWithoutResume;
use NAL\TimeTracker\Exception\UnsupportedLogic;
use NAL\TimeTracker\TimerStatus;
use NAL\TimeTracker\TimeTracker;
use PHPUnit\Framework\TestCase;

class TimeTrackerTest extends TestCase
{
    public function testStartAndEndTimer(): void
    {
        $tracker = new TimeTracker();
        $id = 'test_timer';

        $tracker->start($id);
        usleep(50000);
        $tracker->stop($id);

        $result = $tracker->calculate($id);

        $this->assertNotNull($result);
        $this->assertGreaterThan(0, $result->get());
    }

    public function testEndWithInvalidId(): void
    {
        $this->expectException(TimerNotStarted::class);
        $tracker = new TimeTracker();

        $tracker->stop('invalid_timer');
    }

    public function testCalculateWithInvalidId(): void
    {
        $tracker = new TimeTracker();

        $result = $tracker->calculate('invalid_timer');

        $this->assertNull($result);
    }

    public function testStatus(): void
    {
        $tracker = new TimeTracker();
        $id = 'test_timer';

        $this->assertSame(TimerStatus::NOT_STARTED->value, $tracker->status($id));

        $tracker->start($id);
        $this->assertSame(TimerStatus::IN_PROGRESS->value, $tracker->status($id));

        $tracker->stop($id);
        $this->assertSame(TimerStatus::COMPLETED->value, $tracker->status($id));
    }

    public function testWatch(): void
    {
        $result = TimeTracker::watch(function () {
            usleep(50000);
        });

        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('time', $result);
        $this->assertGreaterThan(0, $result['time']->get());
    }

    public function testWatchWithExceptionThrow(): void
    {
        $this->expectException(RuntimeException::class);

        TimeTracker::watch(function () {
            usleep(50000);
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
        usleep(10000);
        $tracker->stop($id1);

        $tracker->start($id2);
        usleep(20000);
        $tracker->stop($id2);

        $durations = $tracker->durations();

        $this->assertCount(2, $durations);
        $this->assertArrayHasKey($id1, $durations);
        $this->assertArrayHasKey($id2, $durations);
        $this->assertIsString($durations[$id1]);
        $this->assertIsString($durations[$id2]);
        $this->assertStringEndsWith(' ms', $durations[$id1]);
        $this->assertStringEndsWith(' ms', $durations[$id2]);
    }

    public function testResetFunctionality(): void
    {
        $timeTracker = new TimeTracker();

        $timeTracker->start('timer1');
        $timeTracker->start('timer2');
        $timeTracker->stop('timer1');
        $timeTracker->stop('timer2');

        $this->assertTrue($timeTracker->exists('timer1'));
        $this->assertTrue($timeTracker->exists('timer2'));

        $timeTracker->reset('timer1');
        $this->assertFalse($timeTracker->exists('timer1'));
        $this->assertTrue($timeTracker->exists('timer2'));

        $timeTracker->reset();
        $this->assertFalse($timeTracker->exists('timer2'));
    }

    public function testStopWithoutSpecificId(): void
    {
        $timetracker = new TimeTracker();

        $timetracker->start('timer1');
        usleep(10000);
        $timetracker->stop();

        $this->assertTrue($timetracker->exists('timer1'));
    }

    public function testStopThrowExceptionOnCallingWithoutActiveStartRecord(): void
    {
        $this->expectException(NoActiveTimerToStopException::class);

        $timetracker = new TimeTracker();

        $timetracker->start('timer1');
        $timetracker->stop();
        $timetracker->stop();
    }

    public function testStopThrowExceptionOnCallingWithoutStartRecord(): void
    {
        $this->expectException(TimerNotStarted::class);

        $timetracker = new TimeTracker();

        $timetracker->start('timer1');
        $timetracker->stop('timer2');
    }

    public function testIsStarted(): void
    {
        $timetracker = new TimeTracker();

        $timetracker->start('timer1');

        $this->assertTrue($timetracker->isStarted('timer1'));
        $this->assertFalse($timetracker->isStarted('timer2'));
    }

    public function testIsStopped(): void
    {
        $timetracker = new TimeTracker();

        $timetracker->start('timer1');
        usleep(10000);
        $timetracker->stop('timer1');

        $this->assertTrue($timetracker->isStopped('timer1'));
        $this->assertFalse($timetracker->isStopped('timer2'));
    }

    public function testGetEmptyActiveTimersWhenNoActiveRecord(): void
    {
        $timetracker = new TimeTracker();

        $this->assertEmpty($timetracker->getActiveTimers());
    }

    public function testGetActiveTimers(): void
    {
        $timetracker = new TimeTracker();

        $timetracker->start('timer1');
        $timetracker->start('timer2');

        $this->assertSame(['timer1', 'timer2'], $timetracker->getActiveTimers());
    }

    public function testLap(): void
    {
        $timetracker = new TimeTracker();

        $timetracker->start('timer1');

        usleep(10000);
        $timetracker->lap('timer1', 'After 10ms delay');

        usleep(20000);
        $timetracker->lap('timer1', 'After 20ms delay');

        $timetracker->stop();

        $laps = $timetracker->getLaps('timer1');

        $this->assertNotEmpty($laps);

        $expected = [10, 20];
        foreach ($laps as $index => $lap) {
            $this->assertSame("After {$expected[$index]}ms delay", $lap['description']);
        }
    }

    public function testLapThrowExceptionOnCallingWithoutTimerStarted(): void
    {
        $this->expectException(TimerNotStarted::class);

        $timetracker = new TimeTracker();

        $timetracker->lap('timer1');
    }

    public function testPauseThrowsExceptionIfTimerNotStarted(): void
    {
        $tracker = new TimeTracker();

        $this->expectException(TimerNotStarted::class);

        $tracker->pause('task');
    }

    public function testPauseWorksWhenTimerIsStarted(): void
    {
        $tracker = new TimeTracker();

        $tracker->start('task');
        $tracker->pause('task', 'first pause');

        $inspectData = $tracker->inspect('task');
        $pauseData = $inspectData['paused'][0];

        $this->assertSame('first pause', $pauseData['description']);
        $this->assertIsFloat($pauseData['time']);
    }

    public function testPauseThrowsIfAlreadyPausedAndNotResumed(): void
    {
        $tracker = new TimeTracker();

        $tracker->start('task');
        $tracker->pause('task');

        $this->expectException(TimerAlreadyPaused::class);

        $tracker->pause('task');
    }

    public function testResumeThrowsExceptionIfTimerNotStarted(): void
    {
        $tracker = new TimeTracker();

        $this->expectException(TimerNotStarted::class);

        $tracker->resume('task');
    }

    public function testResumeThrowsIfNoActivePause(): void
    {
        $tracker = new TimeTracker();
        $tracker->start('task');

        $this->expectException(NoActivePausedTimerToResume::class);

        $tracker->resume('task');
    }

    public function testResumeWorksAfterPause(): void
    {
        $tracker = new TimeTracker();

        $tracker->start('task');
        $tracker->pause('task', 'pause point');
        usleep(10_000);
        $tracker->resume('task', 'resume point');

        $inspectData = $tracker->inspect('task');
        $resumeData = $inspectData['resumed'][0];

        $this->assertSame('resume point', $resumeData['description']);
        $this->assertIsFloat($resumeData['time']);
    }

    public function testMultiplePauseResumeCycles(): void
    {
        $tracker = new TimeTracker();

        $tracker->start('task');

        $tracker->pause('task');
        usleep(5000);
        $tracker->resume('task');

        $tracker->pause('task');
        usleep(5000);
        $tracker->resume('task');

        $tracker->stop();

        $tracker->calculate('task');

        $this->assertTrue(true);
    }

    public function testCalculateThrowsExceptionForUnmatchedPause(): void
    {
        $tracker = new TimeTracker();

        $id = 'task';

        $tracker->start($id);
        usleep(5000);

        $tracker->pause($id, 'first pause');
        $tracker->resume($id, 'first resume');
        $tracker->pause($id, 'second pause');

        $tracker->stop($id);

        $this->expectException(UnmatchedPauseWithoutResume::class);
        $this->expectExceptionMessage("Unmatched pause without resume for timer with ID '{$id}'");

        $tracker->calculate($id);
    }

    public function testInspectMethod(): void
    {
        $tracker = new TimeTracker();
        $id = 'inspect_timer';

        $inspectData = $tracker->inspect($id);
        $this->assertNull($inspectData['start']);
        $this->assertNull($inspectData['end']);
        $this->assertEmpty($inspectData['paused']);
        $this->assertEmpty($inspectData['resumed']);
        $this->assertSame(TimerStatus::NOT_STARTED->value, $inspectData['status']);
        $this->assertEmpty($inspectData['laps']);

        $tracker->start($id);
        usleep(5000);

        $tracker->lap($id, 'First checkpoint');
        usleep(3000);

        $tracker->pause($id, 'Taking a break');
        usleep(2000);
        $tracker->resume($id, 'Back to work');
        usleep(4000);

        $tracker->lap($id, 'Second checkpoint');

        $tracker->stop($id);

        $inspectData = $tracker->inspect($id);

        $this->assertIsFloat($inspectData['start']);
        $this->assertIsFloat($inspectData['end']);
        $this->assertGreaterThan($inspectData['start'], $inspectData['end']);

        $this->assertCount(1, $inspectData['paused']);
        $this->assertSame('Taking a break', $inspectData['paused'][0]['description']);
        $this->assertIsFloat($inspectData['paused'][0]['time']);

        $this->assertCount(1, $inspectData['resumed']);
        $this->assertSame('Back to work', $inspectData['resumed'][0]['description']);
        $this->assertIsFloat($inspectData['resumed'][0]['time']);

        $this->assertSame(TimerStatus::COMPLETED->value, $inspectData['status']);

        $this->assertCount(2, $inspectData['laps']);
        $this->assertSame('First checkpoint', $inspectData['laps'][0]['description']);
        $this->assertSame('Second checkpoint', $inspectData['laps'][1]['description']);
        $this->assertIsFloat($inspectData['laps'][0]['time']);
        $this->assertIsFloat($inspectData['laps'][1]['time']);
    }
}
