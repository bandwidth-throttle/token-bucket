<?php

namespace bandwidthThrottle\tokenBucket;

use phpmock\environment\SleepEnvironmentBuilder;
use phpmock\environment\MockEnvironment;
use bandwidthThrottle\tokenBucket\storage\SingleProcessStorage;

/**
 * Test for BlockingConsumer.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see BlockingConsumer
 */
class BlockingConsumerTest extends \PHPUnit_Framework_TestCase
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
     * Tests comsumption of cumulated tokens.
     *
     * @test
     */
    public function testConsume()
    {
        $bucket   = new TokenBucket(10, TokenBucket::SECOND, new SingleProcessStorage());
        $consumer = new BlockingConsumer($bucket);
        sleep(10);
        $time = microtime(true);
        
        $consumer->consume(1);
        $consumer->consume(2);
        $consumer->consume(3);
        $consumer->consume(4);
        $this->assertEquals(microtime(true) - $time, 0);
        
        $consumer->consume(1);
        $this->assertEquals(microtime(true) - $time, 1);
        
        sleep(3);
        $time = microtime(true);
        $consumer->consume(4);
        $this->assertEquals(microtime(true) - $time, 1);
    }
    
    /**
     * Tests consume() won't sleep less than one millisecond.
     *
     * @test
     */
    public function testMinimumSleep()
    {
        $bucket   = new TokenBucket(1, 100, new SingleProcessStorage());
        $consumer = new BlockingConsumer($bucket);
        $time     = microtime(true);
        
        $consumer->consume(1);
        $this->assertLessThan(1e-5, abs((microtime(true) - $time) - 0.01));
    }
}
