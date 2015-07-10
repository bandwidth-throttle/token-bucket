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
     * Returns if the storage was already bootstrapped.
     *
     * @return bool True if the storage was already bootstrapped.
     * @internal
     */
    public function isBootstrapped();
    
    /**
     * Bootstraps the storage.
     *
     * @param float $microtime The timestamp.
     * @throws StorageException Bootstrapping failed.
     * @internal
     */
    public function bootstrap($microtime);
    
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
