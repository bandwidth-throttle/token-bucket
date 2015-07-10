<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\lock\Mutex;

/**
 * Token storage.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
interface Storage
{
    
    /**
     * Returns the Mutex for this storage.
     *
     * @return Mutex The mutex.
     * @internal
     */
    public function getMutex();
    
    /**
     * Returns true if the storage was not yet initialized.
     *
     * I.e. the storage was never used before and doesn't have a microtime
     * stored yet.
     *
     * @return bool True, if this is the first usage of the storage.
     * @internal
     */
    public function isUninitialized();
    
    /**
     * Stores a timestamp.
     *
     * @param float $microtime The timestamp.
     * @throws StorageException An error occured.
     * @internal
     */
    public function setMicrotime($microtime);

    /**
     * Returns the stored timestamp.
     *
     * @return float The timestamp.
     * @throws StorageException An error occured.
     * @internal
     */
    public function getMicrotime();
}
