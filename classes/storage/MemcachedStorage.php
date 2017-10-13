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
final class MemcachedStorage implements Storage, GlobalScope
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
            $this->mutex->notify(); // [CAS] Stop TokenBucket::bootstrap()
            return;
        }
        if ($this->memcached->getResultCode() === \Memcached::RES_NOTSTORED) {
            // [CAS] repeat TokenBucket::bootstrap()
            return;
        }
        throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
    }
    
    public function isBootstrapped()
    {
        if ($this->memcached->get($this->key) !== false) {
            $this->mutex->notify(); // [CAS] Stop TokenBucket::bootstrap()
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
            $this->mutex->notify(); // [CAS] Stop TokenBucket::consume()
            return;
        }
        if ($this->memcached->getResultCode() === \Memcached::RES_DATA_EXISTS) {
            // [CAS] repeat TokenBucket::consume()
            return;
        }
        throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
    }

    public function getMicrotime()
    {
        $getDelayed = $this->memcached->getDelayed([$this->key], true);
        if (!$getDelayed) {
            throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
        }
        
        $result = $this->memcached->fetchAll();
        if (!$result) {
            throw new StorageException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
        }
        
        $microtime = $result[0]["value"];
        $this->casToken = $result[0]["cas"];
        if ($this->casToken === null) {
            throw new StorageException("Failed to aquire a CAS token.");
        }
        
        return (double) $microtime;
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
        $this->mutex->notify();
    }
}
