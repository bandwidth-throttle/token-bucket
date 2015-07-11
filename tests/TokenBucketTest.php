<?php

namespace bandwidthThrottle\tokenBucket;

use phpmock\environment\SleepEnvironmentBuilder;
use phpmock\environment\MockEnvironment;
use bandwidthThrottle\tokenBucket\storage\SingleProcessStorage;

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
                ->addNamespace("bandwidthThrottle\\tokenBucket\\converter")
                ->setTimestamp(1417011228);

        $this->sleepEnvironent = $builder->build();
        $this->sleepEnvironent->enable();
    }
    
    protected function tearDown()
    {
        $this->sleepEnvironent->disable();
    }
    
    /**
     * Tests bootstrap() is bootstraps not on already bootstrapped storages.
     *
     * @test
     */
    public function testBootstrapOnce()
    {
        $storage = $this->getMock(SingleProcessStorage::class, ["isBootstrapped", "bootstrap"]);
        $storage->expects($this->any())
                ->method("isBootstrapped")
                ->willReturn(true);
        
        $bucket = new TokenBucket(1, new Rate(1, Rate::SECOND), $storage);
        
        $storage->expects($this->never())
                ->method("bootstrap");
        
        $bucket->bootstrap();
    }
    
    /**
     * Tests bootstrapping sets to 0 tokens.
     *
     * @test
     */
    public function testDefaultBootstrap()
    {
        $rate        = new Rate(1, Rate::SECOND);
        $tokenBucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $tokenBucket->bootstrap();

        $this->assertFalse($tokenBucket->consume(1));
    }

    /**
     * Tests bootstrapping with tokens.
     *
     * @param int $capacity The capacity.
     * @param int $tokens   The initial amount of tokens.
     *
     * @test
     * @dataProvider provideTestBootstrapWithInitialTokens
     */
    public function testBootstrapWithInitialTokens($capacity, $tokens)
    {
        $rate        = new Rate(1, Rate::SECOND);
        $tokenBucket = new TokenBucket($capacity, $rate, new SingleProcessStorage());
        $tokenBucket->bootstrap($tokens);

        $this->assertTrue($tokenBucket->consume($tokens));
        $this->assertFalse($tokenBucket->consume(1));
    }

    /**
     * Returns test cases for testBootstrapWithInitialTokens().
     *
     * @return int[][] Test cases.
     */
    public function provideTestBootstrapWithInitialTokens()
    {
        return [
            [10, 1],
            [10, 10]
        ];
    }
    
    /**
     * Tests comsumption of cumulated tokens.
     *
     * @test
     */
    public function testConsume()
    {
        $rate   = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(10);
        
        $this->assertTrue($bucket->consume(1));
        $this->assertTrue($bucket->consume(2));
        $this->assertTrue($bucket->consume(3));
        $this->assertTrue($bucket->consume(4));
        
        $this->assertFalse($bucket->consume(1));
        
        sleep(3);
        $this->assertFalse($bucket->consume(4, $seconds));
        $this->assertEquals(1, $seconds);
    }

    /**
     * Tests consume() returns the expected amount of seconds to wait.
     *
     * @test
     */
    public function testWaitCalculation()
    {
        $rate   = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(1);
        
        $bucket->consume(3, $seconds);
        $this->assertEquals(2, $seconds);
        sleep(1);
        
        $bucket->consume(3, $seconds);
        $this->assertEquals(1, $seconds);
        sleep(1);
        
        $bucket->consume(3, $seconds);
        $this->assertEquals(0, $seconds);
    }
    
    /**
     * Test token rate.
     *
     * @test
     */
    public function testWaitingAddsTokens()
    {
        $rate   = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap();

        $this->assertFalse($bucket->consume(1));

        sleep(1);
        $this->assertTrue($bucket->consume(1));
        
        sleep(2);
        $this->assertTrue($bucket->consume(2));
    }
    
    /**
     * Tests consuming insuficient tokens wont remove any token.
     *
     * @test
     */
    public function testConsumeInsufficientDontRemoveTokens()
    {
        $rate   = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(1);

        $this->assertFalse($bucket->consume(2, $seconds));
        $this->assertEquals(1, $seconds);

        $this->assertFalse($bucket->consume(2, $seconds));
        $this->assertEquals(1, $seconds);
        
        $this->assertTrue($bucket->consume(1));
    }

    /**
     * Tests consuming tokens.
     *
     * @test
     */
    public function testConsumeSufficientRemoveTokens()
    {
        $rate   = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $bucket->bootstrap(1);

        $this->assertTrue($bucket->consume(1));
        $this->assertFalse($bucket->consume(1, $seconds));
        $this->assertEquals(1, $seconds);
    }
    
    /**
     * Tests bootstrapping with too many tokens.
     *
     * @test
     * @expectedException \LengthException
     */
    public function testInitialTokensTooMany()
    {
        $rate   = new Rate(1, Rate::SECOND);
        $bucket = new TokenBucket(20, $rate, new SingleProcessStorage());
        $bucket->bootstrap(21);
    }
    
    /**
     * Tests consuming more than the capacity.
     *
     * @test
     * @expectedException \LengthException
     */
    public function testConsumeTooMany()
    {
        $rate        = new Rate(1, Rate::SECOND);
        $tokenBucket = new TokenBucket(20, $rate, new SingleProcessStorage());
        $tokenBucket->bootstrap();

        $tokenBucket->consume(21);
    }
    
    /**
     * Test the capacity limit of the bucket
     *
     * @test
     */
    public function testCapacity()
    {
        $rate        = new Rate(1, Rate::SECOND);
        $tokenBucket = new TokenBucket(10, $rate, new SingleProcessStorage());
        $tokenBucket->bootstrap();
        sleep(11);

        $this->assertTrue($tokenBucket->consume(10));
        $this->assertFalse($tokenBucket->consume(1));
    }
}
