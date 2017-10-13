<?php

namespace bandwidthThrottle\tokenBucket\storage;

use malkusch\lock\mutex\SemaphoreMutex;
use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;
use bandwidthThrottle\tokenBucket\util\DoublePacker;

/**
 * Shared memory based storage which can be shared among processes of a single host.
 *
 * This storage is in the global scope. However the scope is limited to the
 * shared memory. I.e. the scope is not shared between hosts.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
final class IPCStorage implements Storage, GlobalScope
{
    
    /**
     * @var Mutex The mutex.
     */
    private $mutex;
    
    /**
     * @var int $key The System V IPC key.
     */
    private $key;
    
    /**
     * @var resource The shared memory.
     */
    private $memory;
    
    /**
     * @var resource The semaphore id.
     */
    private $semaphore;
    
    /**
     * Sets the System V IPC key for the shared memory and its semaphore.
     *
     * You can create the key with PHP's function ftok().
     *
     * @param int $key The System V IPC key.
     *
     * @throws StorageException Could initialize IPC infrastructure.
     */
    public function __construct($key)
    {
        $this->key = $key;
        $this->attach();
    }
    
    /**
     * Attaches the shared memory segment.
     *
     * @throws StorageException Could not initialize IPC infrastructure.
     */
    private function attach()
    {
        try {
            $this->semaphore = sem_get($this->key);
            $this->mutex     = new SemaphoreMutex($this->semaphore);
        } catch (\InvalidArgumentException $e) {
            throw new StorageException("Could not get semaphore id.", 0, $e);
        }
        
        $this->memory = shm_attach($this->key, 128);
        if (!is_resource($this->memory)) {
            throw new StorageException("Failed to attach to shared memory.");
        }
    }
    
    public function bootstrap($microtime)
    {
        if (is_null($this->memory)) {
            $this->attach();
        }
        $this->setMicrotime($microtime);
    }
    
    public function isBootstrapped()
    {
        return !is_null($this->memory) && shm_has_var($this->memory, 0);
    }
    
    public function remove()
    {
        if (!shm_remove($this->memory)) {
            throw new StorageException("Could not release shared memory.");
        }
        $this->memory = null;

        if (!sem_remove($this->semaphore)) {
            throw new StorageException("Could not remove semaphore.");
        }
        $this->semaphore = null;
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function setMicrotime($microtime)
    {
        $data = DoublePacker::pack($microtime);
        if (!shm_put_var($this->memory, 0, $data)) {
            throw new StorageException("Could not store in shared memory.");
        }
    }
    
    /**
     * @SuppressWarnings(PHPMD)
     */
    public function getMicrotime()
    {
        $data = shm_get_var($this->memory, 0);
        if ($data === false) {
            throw new StorageException("Could not read from shared memory.");
        }
        return DoublePacker::unpack($data);
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
    }
}
