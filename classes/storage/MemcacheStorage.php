<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;
use malkusch\lock\mutex\MemcacheMutex;

/**
 * Memcache based storage which can be shared among processes.
 *
 * This storage is in the global scope.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @deprecated 1.0.0 There's no support for the memcache extension under PHP-7.
 *             Consider using ext-mecached and {@link MemcachedStorage}.
 *             This storage will not be removed, however there's no guarantee
 *             that it will work. As soon as ext-memcache is available for PHP-7
 *             the deprecation will be reverted.
 */
final class MemcacheStorage implements Storage, GlobalScope
{

    /**
     * @var \Memcache The connected memcache API.
     */
    private $memcache;
    
    /**
     * @var string The key for the token bucket.
     */
    private $key;
    
    /**
     * @var MemcacheMutex The mutex for this storage.
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
     * @param string    $name     The name of the shared token bucket.
     * @param \Memcache $memcache The connected memcache API.
     */
    public function __construct($name, \Memcache $memcache)
    {
        trigger_error("MemcacheStorage has been deprecated in favour of MemcachedStorage.", E_USER_DEPRECATED);
        
        $this->memcache = $memcache;
        $this->key      = self::PREFIX . $name;
        $this->mutex    = new MemcacheMutex($name, $memcache);
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

    public function letMicrotimeUnchanged()
    {
    }
}
