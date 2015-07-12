<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;
use malkusch\lock\Mutex;

/**
 * Memcache based storage which can be shared among processes.
 *
 * This storage is in the global scope.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class MemcacheStorage implements Storage, GlobalScope
{

    /**
     * @var \Memcache $memcache The connected memcache API.
     */
    private $memcache;
    
    /**
     * @var string The key for the token bucket.
     */
    private $key;
    
    /**
     * @var Mutex The mutex for this storage.
     */
    private $mutex;

    /**
     * @internal
     */
    const PREFIX = "TokenBucket_";
    
    /**
     * Sets the connected memcache API and the token bucket name.
     *
     * The api needs to be connected already. I.e. Memcache::connect() was
     * already called.
     *
     * The Memcache API doesn't provide any mechanism to avoid race conditions.
     * You therefore have to provide a Mutex yourself. Note that the
     * mutex should depend on the $name parameter. You only need to
     * synchronize per bucket. I.e. provide the same mutex for the same name.
     *
     * @param string    $name     The name of the shared token bucket.
     * @param \Memcache $memcache The connected memcache API.
     * @param Mutex     $mutex    The mutex for this storage.
     */
    public function __construct($name, \Memcache $memcache, Mutex $mutex)
    {
        $this->memcache = $memcache;
        $this->key      = self::PREFIX . $name;
        $this->mutex    = $mutex;
    }

    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
    }
    
    public function isBootstrapped()
    {
        return $this->memcache->get($this->key) !== false;
    }
    
    public function remove()
    {
        if (!$this->memcache->delete($this->key)) {
            throw new StorageException("Could not remove microtime.");
        }
    }
    
    public function setMicrotime($microtime)
    {
        if (!$this->memcache->set($this->key, $microtime, 0, 0)) {
            throw new StorageException("Could not set microtime.");
        }
    }

    public function getMicrotime()
    {
        $microtime = $this->memcache->get($this->key);
        if ($microtime === false) {
            throw new StorageException("The key '$this->key' was not found.");
        }
        return (double) $microtime;
    }

    public function getMutex()
    {
        return $this->mutex;
    }
}
