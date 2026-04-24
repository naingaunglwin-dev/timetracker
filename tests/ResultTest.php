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
}
