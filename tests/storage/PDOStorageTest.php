<?php

namespace bandwidthThrottle\tokenBucket\storage;

/**
 * Tests for PDOStorage.
 *
 * If you want to run vendor specific PDO tests you should provide these
 * environment variables:
 *
 * - MYSQL_DSN, MYSQL_USER
 * - PGSQL_DSN, PGSQL_USER
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see PDOStorage
 */
class PDOStorageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests instantiation with a too long name should fail.
     *
     * @test
     * @expectedException \LengthException
     */
    public function testTooLongNameFails()
    {
        $this->markTestIncomplete();
    }

    /**
     * Tests instantiation with PDO in wrong error mode should fail.
     *
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function testPDOInWrongErrorModeFails()
    {
        $this->markTestIncomplete();
    }

    /**
     * Tests bootstrap() would add a row to an existing table.
     *
     * @test
     */
    public function testBootstrapAddsRow()
    {
        $this->markTestIncomplete();
    }

    /**
     * Tests bootstrap() would add a row to an existing table.
     *
     * @test
     * @expectedException bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testBootstrapFailsForExistingRow()
    {
        $this->markTestIncomplete();
    }

    /**
     * Tests remove() removes only one row.
     *
     * @test
     */
    public function testRemoveOneRow()
    {
        $this->markTestIncomplete();
    }

    /**
     * Tests remove() removes the table after the last row.
     *
     * @test
     */
    public function testRemoveTable()
    {
        $this->markTestIncomplete();
    }
}
