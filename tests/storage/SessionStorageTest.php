<?php

namespace bandwidthThrottle\tokenBucket\storage;

/**
 * Tests for SessionStorage.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see SessionStorage
 */
class SessionStorageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests two storages with different names don't interfere each other.
     *
     * @test
     */
    public function testStoragesDontInterfere()
    {
        $storageA = new SessionStorage("A");
        $storageA->bootstrap(0);

        $storageB = new SessionStorage("B");
        $storageB->bootstrap(0);
        
        $storageA->setMicrotime(1);
        $storageB->setMicrotime(2);
        
        $this->assertNotEquals($storageA->getMicrotime(), $storageB->getMicrotime());
    }
}
