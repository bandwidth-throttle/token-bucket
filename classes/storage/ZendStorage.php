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
        $result = ($this->storage->getItem($this->key) !== null);
        $this->mutex->notify();
        return $result;
    }

    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
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
            throw new StorageException('Microtime not stored.');
        }

        $this->casToken = $casToken;
        return (float)$microtime;
    }
}
