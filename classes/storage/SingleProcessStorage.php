<?php

namespace bandwidthThrottle\tokenBucket\storage;

/**
 * In-memory token storage which is only used for one single process.
 *
 * This storage is not shared among processes.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class SingleProcessStorage implements Storage
{
    
    /**
     * @var float The microtime.
     */
    private $microtime;
    
    public function getMicrotime()
    {
        return $this->microtime;
    }

    public function isUninitialized()
    {
        return is_null($this->microtime);
    }

    public function setMicrotime($microtime)
    {
        $this->microtime = $microtime;
    }
}
