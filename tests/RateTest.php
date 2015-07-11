<?php

namespace bandwidthThrottle\tokenBucket;

/**
 * Test for Rate.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see Rate
 */
class RateTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests getRate().
     *
     * @test
     */
    public function testGetRate()
    {
        $this->markTestIncomplete();
    }
    
    /**
     * Tests building a rate with an invalid unit fails.
     *
     * @test
     * @expectedException InvalidArgumentException
     */
    public function testInvalidUnit()
    {
        $this->markTestIncomplete();
    }
}
