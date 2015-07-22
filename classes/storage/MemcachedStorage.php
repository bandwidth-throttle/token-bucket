<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;
use malkusch\lock\mutex\CASMutex;

/**
 * Memcached based storage which can be shared among processes.
 *
 * This storage is in the global scope.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class MemcachedStorage implements Storage, GlobalScope
{

    /**
     * @var \Memcached The memcached API.
     */
    private $memcached;
    
    /**
     * @var float The CAS token.
     */
    private $casToken;
    
    /**
     * @var string The key for the token bucket.
     */
    private $key;
    
    /**
     * @var CASMutex The mutex for this storage.
     */
    private $mutex;

    /**
     * @internal
     */
    const PREFIX = "TokenBucketD_";
    
    /**
     * Sets the memcached API and the token bucket name.
     *
     * The api needs to have at least one server in its pool. I.e.
     * it has to be added with Memcached::addServer().
     *
     * @param string     $name      The name of the shared token bucket.
     * @param \Memcached $memcached The memcached API.
     */
    public function __construct($name, \Memcached $memcached)
    {
        $this->key   = self::PREFIX . $name;
        $this->mutex = new CASMutex();
        $this->memcached = $memcached;
    }
    
    public function bootstrap($microtime)
    {
        if ($this->memcached->add($this->key, $microtime)) {
            $this->mutex->notify(); // [CAS] Stop TockenBucket::bootstrap()
            return;

        }
        if ($this->memcached->getResultCode() === \Memcached::RES_NOTSTORED) {
            // [CAS] repeat TockenBucket::bootstrap()
            return;
        }
        throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
    }
    
    public function isBootstrapped()
    {
        if ($this->memcached->get($this->key) !== false) {
            $this->mutex->notify(); // [CAS] Stop TockenBucket::bootstrap()
            return true;

        }
        if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            return false;
            
        }
        throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
    }
    
    public function remove()
    {
        if (!$this->memcached->delete($this->key)) {
            throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
        }
    }
    
    public function setMicrotime($microtime)
    {
        if (is_null($this->casToken)) {
            throw new StorageException("CAS token is null. Call getMicrotime() first.");
            
        }
        if ($this->memcached->cas($this->casToken, $this->key, $microtime)) {
            $this->mutex->notify(); // [CAS] Stop TockenBucket::consume()
            return;
        }
        if ($this->memcached->getResultCode() === \Memcached::RES_DATA_EXISTS) {
            // [CAS] repeat TockenBucket::consume()
            return;

        }
        throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
    }

    public function getMicrotime()
    {
        $microtime = $this->memcached->get($this->key, null, $this->casToken);
        if ($microtime === false) {
            throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
        }
        return (double) $microtime;
    }

    public function getMutex()
    {
        return $this->mutex;
    }
}
