<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;
use malkusch\lock\mutex\CASMutex;
use Zend\Cache\Storage\StorageInterface;

class ZendStorage implements Storage, GlobalScope
{
    const PREFIX = 'TokenBucket_';

    /** @var string */
    protected $key;

    /** @var StorageInterface */
    protected $storage;

    /** @var CASMutex */
    protected $mutex;

    /** @var mixed */
    protected $casToken;

    public function __construct($name, StorageInterface $storage)
    {
        $this->key = self::PREFIX . $name;
        $this->storage = $storage;
        $this->mutex = new CASMutex();
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function isBootstrapped()
    {
        if ($this->storage->getItem($this->key) !== null) {
            $this->mutex->notify();
            return true;
        }

        return false;
    }

    public function bootstrap($microtime)
    {
        if (!$this->storage->setItem($this->key, $microtime)) {
            throw new StorageException('Could not bootstrap storage.');
        }

        $this->mutex->notify();
    }

    public function remove()
    {
        $this->storage->removeItem($this->key);
    }

    public function letMicrotimeUnchanged()
    {
        $this->mutex->notify();
    }

    public function setMicrotime($microtime)
    {
        if ($this->storage->checkAndSetItem($this->casToken, $this->key, $microtime)) {
            $this->mutex->notify();
        }
    }

    public function getMicrotime()
    {
        $microtime = $this->storage->getItem($this->key, $success, $casToken);

        if (!$microtime) {
            if ($success) {
                throw new StorageException('Stored microtime is invalid.');
            } else {
                throw new StorageException(
                    'Failed to retrieve stored microtime. Did you bootstrap the storage before this point?'
                );
            }
        }

        $this->casToken = $casToken;
        return (float)$microtime;
    }
}
