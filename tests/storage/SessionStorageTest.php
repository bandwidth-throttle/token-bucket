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
        $this->markTestIncomplete();
    }
}
