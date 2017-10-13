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
                ->addNamespace("bandwidthThrottle\\tokenBucket\\util")
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
    public function testConsecutiveConsume()
    {
        $rate     = new Rate(1, Rate::SECOND);
        $bucket   = new TokenBucket(10, $rate, new SingleProcessStorage());
        $consumer = new BlockingConsumer($bucket);
        $bucket->bootstrap(10);
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
     * Tests consume().
     *
     * @param double $expected The expected duration.
     * @param int    $tokens   The tokens to consume.
     * @param Rate   $rate     The rate.
     *
     * @dataProvider provideTestConsume
     * @test
     */
    public function testConsume($expected, $tokens, Rate $rate)
    {
        $bucket   = new TokenBucket(10000, $rate, new SingleProcessStorage());
        $consumer = new BlockingConsumer($bucket);
        $bucket->bootstrap();
        
        $time = microtime(true);
        $consumer->consume($tokens);
        $this->assertEquals($expected, microtime(true) - $time);
    }
    
    /**
     * Returns test cases for testConsume().
     *
     * @return array Test cases.
     */
    public function provideTestConsume()
    {
        return [
            [0.5,  500, new Rate(1, Rate::MILLISECOND)],
            [1,   1000, new Rate(1, Rate::MILLISECOND)],
            [1.5, 1500, new Rate(1, Rate::MILLISECOND)],
            [2,   2000, new Rate(1, Rate::MILLISECOND)],
            [2.5, 2500, new Rate(1, Rate::MILLISECOND)],
        ];
    }
    
    /**
     * Tests consume() won't sleep less than one millisecond.
     *
     * @test
     */
    public function testMinimumSleep()
    {
        $rate   = new Rate(10, Rate::MILLISECOND);
        $bucket = new TokenBucket(1, $rate, new SingleProcessStorage());
        $bucket->bootstrap();

        $consumer = new BlockingConsumer($bucket);
        $time     = microtime(true);
        
        $consumer->consume(1);
        $this->assertLessThan(1e-5, abs((microtime(true) - $time) - 0.001));
    }
    
    /**
     * consume() should fail after a timeout.
     *
     * @expectedException \bandwidthThrottle\tokenBucket\TimeoutException
     * @test
     */
    public function consumeShouldFailAfterTimeout()
    {
        $rate = new Rate(0.1, Rate::SECOND);
        $bucket = new TokenBucket(1, $rate, new SingleProcessStorage());
        $bucket->bootstrap(0);
        $consumer = new BlockingConsumer($bucket, 9);
        
        $consumer->consume(1);
    }
    
    /**
     * consume() should not fail before a timeout.
     *
     * @test
     */
    public function consumeShouldNotFailBeforeTimeout()
    {
        $rate = new Rate(0.1, Rate::SECOND);
        $bucket = new TokenBucket(1, $rate, new SingleProcessStorage());
        $bucket->bootstrap(0);
        $consumer = new BlockingConsumer($bucket, 11);
        
        $consumer->consume(1);
    }
    
    /**
     * consume() should not never time out.
     *
     * @test
     */
    public function consumeWithoutTimeoutShouldNeverFail()
    {
        $rate = new Rate(0.1, Rate::YEAR);
        $bucket = new TokenBucket(1, $rate, new SingleProcessStorage());
        $bucket->bootstrap(0);
        $consumer = new BlockingConsumer($bucket);
        
        $consumer->consume(1);
    }
}
