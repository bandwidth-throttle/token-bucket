<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\lock\Flock;

/**
 * File based storage which can be shared among processes.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class FileStorage implements Storage
{
 
    /**
     * @var Mutex The mutex.
     */
    private $mutex;
    
    /**
     * @var resource The file handle.
     */
    private $fp;
    
    /**
     * Sets the file path and opens it.
     *
     * @param string $path The file path.
     * @throws StorageException Failed to open the file.
     */
    public function __construct($path)
    {
        $this->fp = fopen($path, "c+");
        if (!is_resource($this->fp)) {
            throw new StorageException("Could not open '$path'.");

        }
        $this->mutex = new Flock($this->fp);
    }
    
    public function __destruct()
    {
        fclose($this->fp);
    }
    
    public function isBootstrapped()
    {
        $stats = fstat($this->fp);
        return $stats["size"] > 0;
    }
    
    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
    }

    public function setMicrotime($microtime)
    {
        if (fseek($this->fp, 0) !== 0) {
            throw new StorageException("Could not move to beginning of the file.");
        }

        $data = pack("d", $microtime);
        assert(8 === strlen($data)); // $data is a 64 bit double.

        $result = fwrite($this->fp, $data, strlen($data));
        if ($result !== strlen($data)) {
            throw new StorageException("Could not write to storage.");
        }
    }
    
    public function getMicrotime()
    {
        if (fseek($this->fp, 0) !== 0) {
            throw new StorageException("Could not move to beginning of the file.");

        }
        $data = fread($this->fp, 8);
        if ($data === false) {
            throw new StorageException("Could not read from storage.");
        }
        if (strlen($data) !== 8) {
            throw new StorageException("Could not read 64 bit from storage.");

        }
        $unpack = unpack("d", $data);
        if (!is_array($unpack) || !array_key_exists(1, $unpack)) {
            throw new StorageException("Could not unpack storage content.");

        }
        return $unpack[1];
    }
    
    public function getMutex()
    {
        return $this->mutex;
    }
}
