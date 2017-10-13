<?php

namespace bandwidthThrottle\tokenBucket\storage;

use malkusch\lock\mutex\FlockMutex;
use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;
use bandwidthThrottle\tokenBucket\util\DoublePacker;

/**
 * File based storage which can be shared among processes.
 *
 * This storage is in the global scope. However the scope is limited to the
 * underlying filesystem. I.e. the scope is not shared between hosts.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
final class FileStorage implements Storage, GlobalScope
{
 
    /**
     * @var Mutex The mutex.
     */
    private $mutex;
    
    /**
     * @var resource The file handle.
     */
    private $fileHandle;
    
    /**
     * @var string The file path.
     */
    private $path;
    
    /**
     * Sets the file path and opens it.
     *
     * If the file does not exist yet, it will be created. This is an atomic
     * operation.
     *
     * @param string $path The file path.
     * @throws StorageException Failed to open the file.
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->open();
    }
    
    /**
     * Opens the file and initializes the mutex.
     *
     * @throws StorageException Failed to open the file.
     */
    private function open()
    {
        $this->fileHandle = fopen($this->path, "c+");
        if (!is_resource($this->fileHandle)) {
            throw new StorageException("Could not open '$this->path'.");
        }
        $this->mutex = new FlockMutex($this->fileHandle);
    }
    
    /**
     * Closes the file handle.
     *
     * @internal
     */
    public function __destruct()
    {
        fclose($this->fileHandle);
    }
    
    public function isBootstrapped()
    {
        $stats = fstat($this->fileHandle);
        return $stats["size"] > 0;
    }
    
    public function bootstrap($microtime)
    {
        $this->open(); // remove() could have deleted the file.
        $this->setMicrotime($microtime);
    }
    
    public function remove()
    {
        // Truncate to notify isBootstrapped() about the new state.
        if (!ftruncate($this->fileHandle, 0)) {
            throw new StorageException("Could not truncate $this->path");
        }
        if (!unlink($this->path)) {
            throw new StorageException("Could not delete $this->path");
        }
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function setMicrotime($microtime)
    {
        if (fseek($this->fileHandle, 0) !== 0) {
            throw new StorageException("Could not move to beginning of the file.");
        }
        
        $data = DoublePacker::pack($microtime);
        $result = fwrite($this->fileHandle, $data, strlen($data));
        if ($result !== strlen($data)) {
            throw new StorageException("Could not write to storage.");
        }
    }
    
    /**
     * @SuppressWarnings(PHPMD)
     */
    public function getMicrotime()
    {
        if (fseek($this->fileHandle, 0) !== 0) {
            throw new StorageException("Could not move to beginning of the file.");
        }
        $data = fread($this->fileHandle, 8);
        if ($data === false) {
            throw new StorageException("Could not read from storage.");
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
