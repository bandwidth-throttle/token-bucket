<?php

namespace bandwidthThrottle\tokenBucket;

use phpmock\environment\SleepEnvironmentBuilder;
use phpmock\environment\MockEnvironment;

/**
 * Test for TokenBucket.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see TokenBucket
 */
class TokenBucketTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var MockEnvironment Mock for microtime() and usleep().
     */
    private $sleepEnvironent;
    
    protected function setUp()
    {
        $builder = new SleepEnvironmentBuilder();
        $builder->addNamespace(__NAMESPACE__)
                ->setTimestamp(1417011228);

        $this->sleepEnvironent = $builder->build();
        $this->sleepEnvironent->enable();
    }
    
    protected function tearDown()
    {
        $this->sleepEnvironent->disable();
    }
    
    /**
     * Tests the intial bucket is empty
     *
     * @test
     */
    public function testInitialBucketIsEmpty()
    {
        $tokenBucket = new TokenBucket(10, TokenBucket::SECOND);
        $time        = microtime(true);

        $tokenBucket->consume(1);
        $this->assertEquals(microtime(true) - $time, 1);
    }
    
    /**
     * Tests consuming more than the capacity.
     *
     * @test
     * @expectedException \LengthException
     */
    public function testConsumeTooMuch()
    {
        $tokenBucket = new TokenBucket(20, TokenBucket::SECOND);
        $tokenBucket->consume(21);
    }
    
    /**
     * Test the capacity limit of the bucket
     *
     * @test
     */
    public function testCapacity()
    {
        $tokenBucket = new TokenBucket(10, TokenBucket::SECOND);
        sleep(11);

        $time = microtime(true);
        $tokenBucket->consume(10);
        $tokenBucket->consume(1);
        $this->assertEquals(microtime(true) - $time, 1);
    }
    
    /**
     * Tests comsumption of cumulated tokens.
     * 
     * @test
     */
    public function testConsumption()
    {
        $tokenBucket = new TokenBucket(10, TokenBucket::SECOND);
        sleep(10);
        $time = microtime(true);
        
        $tokenBucket->consume(1);
        $tokenBucket->consume(2);
        $tokenBucket->consume(3);
        $tokenBucket->consume(4);
        $this->assertEquals(microtime(true) - $time, 0);
        
        $tokenBucket->consume(1);
        $this->assertEquals(microtime(true) - $time, 1);
        
        sleep(3);
        $time = microtime(true);
        $tokenBucket->consume(4);
        $this->assertEquals(microtime(true) - $time, 1);
    }
    
    /**
     * Tests consume() won't sleep less than one millisecond.
     * 
     * @test
     */
    public function testMinimumSleep()
    {
        $tokenBucket = new TokenBucket(10, 1);
        $time        = microtime(true);

        $tokenBucket->consume(1);
        $this->assertLessThan(abs((microtime(true) - $time) - 0.001), 1e-10);
    }
}
