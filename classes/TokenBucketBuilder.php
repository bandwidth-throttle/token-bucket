<?php

namespace bandwidthThrottle\tokenBucket;

/**
 * Token Bucket builder.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class TokenBucketBuilder
{
    
    /**
     * @var int Token add rate in microseconds.
     */
    private $microRate;
    
    /**
     * @var int Token capacity in bytes.
     */
    private $capacity;
    
    /**
     * Set the rate in bytes per second.
     *
     * @param int $bytes Bytes per second rate.
     */
    public function setRateInBytesPerSecond($bytes)
    {
        $this->microRate = TokenBucket::SECOND / $bytes;
    }
    
    /**
     * Set the rate in kibibytes per second.
     *
     * @param int $kibibytes Kibibytes per second rate.
     */
    public function setRateInKiBperSecond($kibibytes)
    {
        $this->setRateInBytesPerSecond($kibibytes * 1024);
    }
    
    /**
     * Set the rate in mebibytes per second.
     *
     * @param int $mebibytes mebibytes per second rate.
     */
    public function setRateInMiBPerSecond($mebibytes)
    {
        $this->setRateInKiBperSecond($mebibytes * 1024);
    }
    
    /**
     * Sets the capacity in bytes.
     *
     * @param int $bytes The capacity in bytes.
     */
    public function setCapacityInBytes($bytes)
    {
        $this->capacity = $bytes;
    }
    
    /**
     * Sets the capacity in kibibytes.
     *
     * @param int $kibibytes The capacity in kibibytes.
     */
    public function setCapacityInKiB($kibibytes)
    {
        $this->setCapacityInBytes($kibibytes * 1024);
    }
    
    /**
     * Sets the capacity in mebibytes.
     *
     * @param int $mebibytes The capacity in mebibytes.
     */
    public function setCapacityInMiB($mebibytes)
    {
        $this->setCapacityInKiB($mebibytes * 1024);
    }
    
    /**
     * Builds the Token Bucket.
     *
     * @return TokenBucket The Token Bucket
     */
    public function build()
    {
        return new TokenBucket($this->capacity, $this->microRate);
    }
}
