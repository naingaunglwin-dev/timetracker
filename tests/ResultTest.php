<?php

use NAL\TimeTracker\Exception\UnknownUnit;
use NAL\TimeTracker\Result;
use NAL\TimeTracker\Unit;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testFormat(): void
    {
        $unit = new Unit();
        $result = new Result($unit, 123.456, 'ms');

        $formatted = $result->format('{time} {unit}');

        $this->assertSame('123.456 ms', $formatted->get());
    }

    public function testFormatKeepsRawValueSeparate(): void
    {
        $unit = new Unit();
        $result = new Result($unit, 123.456, 'ms');

        $formatted = $result->format('{time}{unit}');

        $this->assertSame('123.456ms', $formatted->get());
        $this->assertSame(123.456, $formatted->value());
        $this->assertSame('ms', $formatted->unit());
    }

    public function testConvert(): void
    {
        $unit = new Unit();
        $result = new Result($unit, 1, 's');

        $converted = $result->convert('ms');

        $this->assertSame(1000, $converted->get());
    }

    public function testConvertAfterFormat(): void
    {
        $unit = new Unit();
        $result = new Result($unit, 1, 's');

        $converted = $result->format('{time}{unit}')->convert('ms');

        $this->assertSame('1000ms', $converted->get());
        $this->assertSame(1000, $converted->value());
        $this->assertSame('ms', $converted->unit());
    }

    public function testUnknownUnit(): void
    {
        $this->expectException(UnknownUnit::class);

        $unit = new Unit();
        $result = new Result($unit, 1, 's');
        $result->convert('unknown_unit');
    }

    public function testConvertOneUnitToAnotherUnit(): void
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

    public function testResultToString(): void
    {
        $result = new Result(new Unit(), 10, 's');

        $this->assertSame('10', "$result");
    }

    public function testFormattedResultToString(): void
    {
        $result = (new Result(new Unit(), 10, 's'))->format('{time} {unit}');

        $this->assertSame('10 s', "$result");
    }

    public function testToArray(): void
    {
        $result = (new Result(new Unit(), 10, 's'))->format('{time} {unit}');

        $this->assertSame([
            'time'      => 10,
            'unit'      => 's',
            'formatted' => '10 s',
        ], $result->toArray());
    }
}
