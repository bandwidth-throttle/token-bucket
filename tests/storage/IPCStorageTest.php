<?php

namespace bandwidthThrottle\tokenBucket\storage;

/**
 * Tests for IPCStorage.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see IPCStorage
 */
class IPCStorageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests building fails for an invalid key.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testBuildFailsForInvalidKey()
    {
        @new IPCStorage("invalid");
    }

    /**
     * Tests remove() fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     * @expectedExceptionMessage Could not release shared memory.
     */
    public function testRemoveFails()
    {
        $storage = new IPCStorage(ftok(__FILE__, "a"));
        $storage->remove();
        @$storage->remove();
    }

    /**
     * Tests removing semaphore fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     * @expectedExceptionMessage Could not remove semaphore.
     */
    public function testfailRemovingSemaphore()
    {
        $key     = ftok(__FILE__, "a");
        $storage = new IPCStorage($key);
        
        sem_remove(sem_get($key));
        @$storage->remove();
    }

    /**
     * Tests setMicrotime() fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testSetMicrotimeFails()
    {
        $storage = new IPCStorage(ftok(__FILE__, "a"));
        $storage->remove();
        @$storage->setMicrotime(123);
    }

    /**
     * Tests getMicrotime() fails.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testGetMicrotimeFails()
    {
        $storage = new IPCStorage(ftok(__FILE__, "b"));
        @$storage->getMicrotime();
    }
}
