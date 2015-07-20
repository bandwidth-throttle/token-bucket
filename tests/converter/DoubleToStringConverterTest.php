<?php

namespace bandwidthThrottle\tokenBucket\converter;

/**
 * Tests for DoubleToStringConverter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see DoubleToStringConverter
 */
class DoubleToStringConverterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests convert().
     *
     * @param string $expected The expected string.
     * @param double $input    The input double.
     *
     * @test
     * @dataProvider provideTestConvert
     */
    public function testConvert($expected, $input)
    {
        $converter = new DoubleToStringConverter();
        $this->assertEquals($expected, $converter->convert($input));
    }
    
    /**
     * Provides test cases for testConvert().
     *
     * @return array Test cases.
     */
    public function provideTestConvert()
    {
        return [
            [pack("d", 0)  , 0],
            [pack("d", 0.1), 0.1],
            [pack("d", 1)  , 1],
        ];
    }
}
