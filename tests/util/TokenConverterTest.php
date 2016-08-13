<?php

namespace bandwidthThrottle\tokenBucket\util;

use bandwidthThrottle\tokenBucket\Rate;
use phpmock\phpunit\PHPMock;

/**
 * Tests for TokenConverter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see TokenConverter
 */
class TokenConverterTest extends \PHPUnit_Framework_TestCase
{

    use PHPMock;
    
    /**
     * Tests convertSecondsToTokens().
     *
     * @param int    $expected The expected tokens.
     * @param double $seconds  The seconds.
     * @param Rate   $rate     The rate.
     *
     * @test
     * @dataProvider provideTestConvertSecondsToTokens
     */
    public function testConvertSecondsToTokens($expected, $seconds, Rate $rate)
    {
        $converter = new TokenConverter($rate);
        $this->assertEquals($expected, $converter->convertSecondsToTokens($seconds));
    }
    
    /**
     * Provides test cases for testConvertSecondsToTokens().
     *
     * @return array Test cases.
     */
    public function provideTestConvertSecondsToTokens()
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
    
    /**
     * Tests convertTokensToSeconds().
     *
     * @param double $expected The expected seconds.
     * @param int    $tokens   The tokens.
     * @param Rate   $rate     The rate.
     *
     * @test
     * @dataProvider provideTestconvertTokensToSeconds
     */
    public function testconvertTokensToSeconds($expected, $tokens, Rate $rate)
    {
        $converter = new TokenConverter($rate);
        $this->assertEquals($expected, $converter->convertTokensToSeconds($tokens));
    }
    
    /**
     * Provides test cases for testconvertTokensToSeconds().
     *
     * @return array Test cases.
     */
    public function provideTestconvertTokensToSeconds()
    {
        return [
            [0.001, 1, new Rate(1, Rate::MILLISECOND)],
            [0.002, 2, new Rate(1, Rate::MILLISECOND)],
            [1, 1, new Rate(1, Rate::SECOND)],
            [2, 2, new Rate(1, Rate::SECOND)],
        ];
    }
    
    /**
     * Tests convertTokensToMicrotime().
     *
     * @param double $delta  The expected delta.
     * @param int    $tokens The tokens.
     * @param Rate   $rate   The rate.
     *
     * @test
     * @dataProvider provideTestConvertTokensToMicrotime
     */
    public function testConvertTokensToMicrotime($delta, $tokens, Rate $rate)
    {
        $microtime = $this->getFunctionMock(__NAMESPACE__, "microtime");
        $microtime->expects($this->any())->willReturn(100000);

        $converter = new TokenConverter($rate);

        $this->assertEquals(microtime(true) + $delta, $converter->convertTokensToMicrotime($tokens));
    }
    
    /**
     * Provides test cases for testConvertTokensToMicrotime().
     *
     * @return array Test cases.
     */
    public function provideTestConvertTokensToMicrotime()
    {
        return [
            [-1, 1, new Rate(1, Rate::SECOND)],
            [-2, 2, new Rate(1, Rate::SECOND)],
            [-0.001, 1, new Rate(1, Rate::MILLISECOND)],
        ];
    }
}
