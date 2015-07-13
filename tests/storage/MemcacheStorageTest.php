<?php

namespace bandwidthThrottle\tokenBucket\storage;

/**
 * Tests for MemcacheStorage.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see MemcacheStorage
 */
class MemcacheStorageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var MemcacheStorage The SUT.
     */
    private $storage;
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->storage = new MemcacheStorage("test", new \Memcache());
    }

    /**
     * Tests remove() fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testRemoveFails()
    {
        @$this->storage->remove();
    }

    /**
     * Tests setMicrotime() fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testSetMicrotime()
    {
        @$this->storage->setMicrotime(1234);
    }

    /**
     * Tests getMicrotime() fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testGetMicrotime()
    {
        @$this->storage->getMicrotime();
    }
}
