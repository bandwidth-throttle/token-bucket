<?php

namespace bandwidthThrottle\tokenBucket;

use phpmock\environment\SleepEnvironmentBuilder;
use phpmock\environment\MockEnvironment;

/**
 * Test for TokenBucketBuilder.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see TokenBucketBuilder
 */
class TokenBucketBuilderTest extends \PHPUnit_Framework_TestCase
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
     * Tests setting the rate.
     *
     * @test
     */
    public function testSetRate()
    {
        $builder = new TokenBucketBuilder();
        $builder->setRate(1, TokenBucketBuilder::KILOBYTES);
        $bucket = $builder->build();
        
        $time = microtime(true);
        $bucket->consume(1000);
        $this->assertEquals(1, microtime(true) - $time);
    }
    
    /**
     * Tests setting the default capacity.
     *
     * @test
     */
    public function testDefaultCapacity()
    {
        $builder = new TokenBucketBuilder();
        $builder->setRate(123);
        $bucket = $builder->build();
        $this->assertEquals(123, $bucket->getCapacity());
    }
    
    /**
     * Tests conversion fails.
     *
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function testConversionFails()
    {
        $builder = new TokenBucketBuilder();
        $builder->setRate(123, "invalid");
    }
    
    /**
     * Test unit conversion.
     *
     * @param int    $expected The expected converted amount
     * @param int    $amount   The amount.
     * @param string $unit     The amount's unit.
     *
     * @dataProvider provideTestConversion
     */
    public function testConversion($expected, $amount, $unit)
    {
        $builder = new TokenBucketBuilder();
        $builder->setCapacity($amount, $unit);
        $bucket = $builder->build();
        $this->assertEquals($expected, $bucket->getCapacity());
    }
    
    /**
     * Returns test cases for testConversion().
     *
     * @return array Test cases.
     */
    public function provideTestConversion()
    {
        return [
            [1,       1, TokenBucketBuilder::BYTES],
            [2000,    2, TokenBucketBuilder::KILOBYTES],
            [2048,    2, TokenBucketBuilder::KIBIBYTES],
            [2000000, 2, TokenBucketBuilder::MEGABYTES],
            [2097152, 2, TokenBucketBuilder::MEBIBYTES],
        ];
    }
}
