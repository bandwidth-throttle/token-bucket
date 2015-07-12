<?php

namespace bandwidthThrottle\tokenBucket\converter;

use bandwidthThrottle\tokenBucket\Rate;
use phpmock\phpunit\PHPMock;

/**
 * Tests for TokenToMicrotimeConverter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see TokenToMicrotimeConverter
 */
class TokenToMicrotimeConverterTest extends \PHPUnit_Framework_TestCase
{

    use PHPMock;
    
    /**
     * Tests convert().
     *
     * @param double $delta  The expected delta.
     * @param int    $tokens The tokens.
     * @param Rate   $rate   The rate.
     *
     * @test
     * @dataProvider provideTestConvert
     */
    public function testConvert($delta, $tokens, Rate $rate)
    {
        $microtime = $this->getFunctionMock(__NAMESPACE__, "microtime");
        $microtime->expects($this->any())->willReturn(100000);

        $converter = new TokenToMicrotimeConverter(new TokenToSecondConverter($rate));

        $this->assertEquals(microtime(true) + $delta, $converter->convert($tokens));
    }
    
    /**
     * Provides test cases for testConvert().
     *
     * @return array Test cases.
     */
    public function provideTestConvert()
    {
        return [
            [-1, 1, new Rate(1, Rate::SECOND)],
            [-2, 2, new Rate(1, Rate::SECOND)],
            [-0.001, 1, new Rate(1, Rate::MILLISECOND)],
        ];
    }
}
