<?php

namespace bandwidthThrottle\tokenBucket\storage;

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
     * Returns true if the storage was not yet initialized.
     *
     * I.e. the storage was never used before and doesn't have a microtime
     * stored yet.
     *
     * @return bool True, if this is the first usage of the storage.
     */
    public function isUninitialized();
    
    /**
     * Stores a timestamp.
     *
     * @param float $microtime The timestamp.
     * @throws StorageException An error occured.
     */
    public function setMicrotime($microtime);

    /**
     * Returns the stored timestamp.
     *
     * @return float The timestamp.
     * @throws StorageException An error occured.
     */
    public function getMicrotime();
}
