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
     * @throws StorageException Checking the state of the storage failed.
     * @internal
     */
    public function isBootstrapped();
    
    /**
     * Bootstraps the storage.
     *
     * @param double $microtime The timestamp.
     * @throws StorageException Bootstrapping failed.
     * @internal
     */
    public function bootstrap($microtime);
    
    /**
     * Removes the storage.
     *
     * After a storage was removed you should not use that object anymore.
     * The only defined methods after that operations are isBootstrapped()
     * and bootstrap(). A call to bootstrap() results in a defined object
     * again.
     *
     * @throws StorageException Cleaning failed.
     * @internal
     */
    public function remove();
    
    /**
     * Stores a timestamp.
     *
     * @param double $microtime The timestamp.
     * @throws StorageException Writing to the storage failed.
     * @internal
     */
    public function setMicrotime($microtime);

    /**
     * Returns the stored timestamp.
     *
     * @return double The timestamp.
     * @throws StorageException Reading from the storage failed.
     * @internal
     */
    public function getMicrotime();
}
