<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\lock\NoMutex;

/**
 * Session based storage which is shared for one user accross requests.
 *
 * As PHP's session are thread safe this implementation doesn't provide a
 * locking Mutex.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class SessionStorage implements Storage
{
 
    /**
     * @var Mutex The mutex.
     */
    private $mutex;
 
    /**
     * @var String The session key for this bucket.
     */
    private $key;
    
    /**
     * @internal
     */
    const SESSION_NAMESPACE = "TokenBucket_";
    
    /**
     * Sets the bucket's name.
     *
     * @param string $name The bucket's name.
     */
    public function __construct($name)
    {
        $this->mutex = new NoMutex();
        $this->key   = self::SESSION_NAMESPACE . $name;
    }

    /**
     * Returns a non locking mutex.
     *
     * This storage doesn't need a mutex at all.
     *
     * @return NoMutex The non locking mutex.
     * @internal
     */
    public function getMutex()
    {
        return $this->mutex;
    }
    
    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function getMicrotime()
    {
        return $_SESSION[$this->key];
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function isBootstrapped()
    {
        return isset($_SESSION[$this->key]);
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function remove()
    {
        unset($_SESSION[$this->key]);
    }

    /**
     * @SuppressWarnings(PHPMD)
     * @internal
     */
    public function setMicrotime($microtime)
    {
        $_SESSION[$this->key] = $microtime;
    }
}
