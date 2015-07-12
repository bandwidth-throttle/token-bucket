<?php

namespace bandwidthThrottle\tokenBucket\converter;

use bandwidthThrottle\tokenBucket\Rate;

/**
 * Tests for TokenToSecondConverter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see TokenToSecondConverter
 */
class TokenToSecondConverterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests convert().
     *
     * @param double $expected The expected seconds.
     * @param int    $tokens   The tokens.
     * @param Rate   $rate     The rate.
     *
     * @test
     * @dataProvider provideTestConvert
     */
    public function testConvert($expected, $tokens, Rate $rate)
    {
        $converter = new TokenToSecondConverter($rate);
        $this->assertEquals($expected, $converter->convert($tokens));
    }
    
    /**
     * Provides test cases for testConvert().
     *
     * @return array Test cases.
     */
    public function provideTestConvert()
    {
        return [
            [0.001, 1, new Rate(1, Rate::MILLISECOND)],
            [0.002, 2, new Rate(1, Rate::MILLISECOND)],
            [1, 1, new Rate(1, Rate::SECOND)],
            [2, 2, new Rate(1, Rate::SECOND)],
        ];
    }
}
