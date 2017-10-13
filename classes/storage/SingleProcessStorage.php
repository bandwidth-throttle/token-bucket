<?php

namespace bandwidthThrottle\tokenBucket\storage;

use malkusch\lock\mutex\NoMutex;
use bandwidthThrottle\tokenBucket\storage\scope\RequestScope;

/**
 * In-memory token storage which is only used for one single process.
 *
 * This storage is in the request scope. It is not shared among processes and
 * therefore needs no locking.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
final class SingleProcessStorage implements Storage, RequestScope
{
 
    /**
     * @var Mutex The mutex.
     */
    private $mutex;
    
    /**
     * @var double The microtime.
     */
    private $microtime;
    
    /**
     * Initialization.
     */
    public function __construct()
    {
        $this->mutex = new NoMutex();
    }
    
    public function isBootstrapped()
    {
        return ! is_null($this->microtime);
    }
    
    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
    }
    
    public function remove()
    {
        $this->microtime = null;
    }

    public function setMicrotime($microtime)
    {
        $this->microtime = $microtime;
    }
    
    public function getMicrotime()
    {
        return $this->microtime;
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
    }
}
