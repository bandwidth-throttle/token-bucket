<?php

namespace bandwidthThrottle\tokenBucket\converter;

use bandwidthThrottle\tokenBucket\Rate;

/**
 * Tests for SecondToTokenConverter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see SecondToTokenConverter
 */
class SecondToTokenConverterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests convert().
     *
     * @param int    $expected The expected tokens.
     * @param double $seconds  The seconds.
     * @param Rate   $rate     The rate.
     *
     * @test
     * @dataProvider provideTestConvert
     */
    public function testConvert($expected, $seconds, Rate $rate)
    {
        $converter = new SecondToTokenConverter($rate);
        $this->assertEquals($expected, $converter->convert($seconds));
    }
    
    /**
     * Provides test cases for testConvert().
     *
     * @return array Test cases.
     */
    public function provideTestConvert()
    {
        return [
            [0, 0.9, new Rate(1, Rate::SECOND)],
            [1, 1,   new Rate(1, Rate::SECOND)],
            [1, 1.1, new Rate(1, Rate::SECOND)],

            [1000, 1, new Rate(1, Rate::MILLISECOND)],
            [2000, 2, new Rate(1, Rate::MILLISECOND)],

            [0, 59, new Rate(1, Rate::MINUTE)],
            [1, 60, new Rate(1, Rate::MINUTE)],
            [1, 61, new Rate(1, Rate::MINUTE)],
        ];
    }
}
