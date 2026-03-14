<?php

namespace App\Tests\Unit\Helper;

use App\Helper\CellHelper;
use PHPUnit\Framework\TestCase;

class CellHelperTest extends TestCase
{
    public function testStringifyCoordinates(): void
    {
        $this->assertSame('5.10', CellHelper::stringifyCoordinates(5, 10));
        $this->assertSame('0.0', CellHelper::stringifyCoordinates(0, 0));
        $this->assertSame('100.200', CellHelper::stringifyCoordinates(100, 200));
    }

    public function testGetDataFromSlug(): void
    {
        $slug = '3.7_1_0:1:0:1';
        $data = CellHelper::getDataFromSlug($slug);

        $this->assertSame('3.7', $data['coordinates']);
        $this->assertSame(1, $data['movement']);
        $this->assertSame(3, $data['x']);
        $this->assertSame(7, $data['y']);
        $this->assertSame(0, $data['north']);
        $this->assertSame(1, $data['east']);
        $this->assertSame(0, $data['south']);
        $this->assertSame(1, $data['west']);
    }

    public function testGetDataFromSlugUnreachable(): void
    {
        $slug = '10.20_-1_1:1:1:1';
        $data = CellHelper::getDataFromSlug($slug);

        $this->assertSame(-1, $data['movement']);
        $this->assertSame(10, $data['x']);
        $this->assertSame(20, $data['y']);
    }

    public function testMoveUnreachableConstant(): void
    {
        $this->assertSame(-1, CellHelper::MOVE_UNREACHABLE);
        $this->assertSame(0, CellHelper::MOVE_DEFAULT);
    }
}
