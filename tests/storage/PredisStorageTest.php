<?php

namespace bandwidthThrottle\tokenBucket\storage;

use Predis\Client;
use Predis\ClientException;

/**
 * Tests for PredisStorage.
 *
 * These tests need the environment variable REDIS_URI.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see PredisStorage
 */
class PredisStorageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Client The API.
     */
    private $redis;

    /**
     * @var PredisStorage The SUT.
     */
    private $storage;
    
    protected function setUp()
    {
        parent::setUp();
        
        if (!getenv("REDIS_URI")) {
            $this->markTestSkipped();
        }
        $this->redis   = new Client(getenv("REDIS_URI"));
        $this->storage = new PredisStorage("test", $this->redis);
    }
    
    /**
     * Tests broken server communication.
     *
     * @param callable $method The tested method.
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     * @dataProvider provideTestBrokenCommunication
     */
    public function testBrokenCommunication(callable $method)
    {
        $redis = $this->createMock(Client::class);
        $redis->expects($this->once())->method("__call")
                ->willThrowException(new ClientException());
        $storage = new PredisStorage("test", $redis);
        call_user_func($method, $storage);
    }

    /**
     * Provides test cases for testBrokenCommunication().
     *
     * @return array Testcases.
     */
    public function provideTestBrokenCommunication()
    {
        return [
            [function (PredisStorage $storage) {
                $storage->bootstrap(1);
            }],
            [function (PredisStorage $storage) {
                $storage->isBootstrapped();
            }],
            [function (PredisStorage $storage) {
                $storage->remove();
            }],
            [function (PredisStorage $storage) {
                $storage->setMicrotime(1);
            }],
            [function (PredisStorage $storage) {
                $storage->getMicrotime();
            }],
        ];
    }
    
    /**
     * Tests remove() fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testRemoveFails()
    {
        $this->storage->bootstrap(1);
        $this->storage->remove();

        $this->storage->remove();
    }
    
    /**
     * Tests setMicrotime() fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testSetMicrotimeFails()
    {
        $redis = $this->createMock(Client::class);
        $redis->expects($this->once())->method("__call")
                ->with("set")
                ->willReturn(false);
        $storage = new PredisStorage("test", $redis);
        $storage->setMicrotime(1);
    }
    
    /**
     * Tests getMicrotime() fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testGetMicrotimeFails()
    {
        $this->storage->bootstrap(1);
        $this->storage->remove();

        $this->storage->getMicrotime();
    }
}
