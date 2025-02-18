<?php

use PHPUnit\Framework\TestCase;

class PhpUnitReadyTest extends TestCase
{
    public function testAddition(): void
    {
        $this->assertEquals(4, 2 + 2);
    }

    public function testStringManipulation(): void
    {
        $this->assertEquals('HELLO', strtoupper('hello'));
        $this->assertStringContainsString('lo', 'hello');
    }

    public function testArrayOperations(): void
    {
        $array = [1, 2, 3];
        $this->assertCount(3, $array);
        $this->assertContains(2, $array);
        $this->assertNotContains(4, $array);
    }

    public function testObjectInstance(): void
    {
        $object = new stdClass();
        $this->assertInstanceOf(stdClass::class, $object);
    }

    public function testExceptionHandling(): void
    {
        $this->expectException(InvalidArgumentException::class);
        throw new InvalidArgumentException("This is a test exception");
    }
}
