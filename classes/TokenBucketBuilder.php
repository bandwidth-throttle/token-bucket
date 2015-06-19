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
     * Unit for bytes.
     */
    const BYTES = "bytes";

    /**
     * Unit for kilobytes (1000 bytes).
     */
    const KILOBYTES = "kilobytes";

    /**
     * Unit for kibibytes (1024 bytes).
     */
    const KIBIBYTES = "kibibytes";

    /**
     * Unit for megabytes (1000 kilobytes).
     */
    const MEGABYTES = "megabytes";

    /**
     * Unit for mebibytes (1024 kibibytes).
     */
    const MEBIBYTES = "mebibytes";

    /**
     * @var int[] The unit map.
     */
    private static $unitMap = [
        self::BYTES     => 1,
        self::KILOBYTES => 1000,
        self::KIBIBYTES => 1024,
        self::MEGABYTES => 1000000,
        self::MEBIBYTES => 1048576,
    ];
    
    /**
     * @var int Token add rate in microseconds.
     */
    private $microRate;
    
    /**
     * @var int Token capacity in bytes.
     */
    private $capacity;
    
    /**
     * @var int The amount of initial tokens, default is 0.
     */
    private $initialTokens = 0;

    /**
     * Sets the amount of initial tokens.
     *
     * Setting the initial amount is optional. Default is 0.
     *
     * @param int    $tokens The initial amount of tokens.
     * @param string $unit   The unit for the amount, default is bytes.
     *
     * @throws \InvalidArgumentException The unit was invalid.
     */
    public function setInitialTokens($tokens, $unit = self::BYTES)
    {
        $this->initialTokens = $this->convertToBytes($tokens, $unit);
    }
    
    /**
     * Sets the rate of token production per second.
     *
     * @param int    $rate The amount of tokens per second.
     * @param string $unit The unit for the amount, default is bytes.
     *
     * @throws \InvalidArgumentException The unit was invalid.
     */
    public function setRate($rate, $unit = self::BYTES)
    {
        $this->microRate = TokenBucket::SECOND / $this->convertToBytes($rate, $unit);
    }

    /**
     * Sets the capacity of the token bucket.
     *
     * Setting the capacity is optional. If no capacity was set, the capacity
     * is set to the amount of tokens which will be produced in one second.
     *
     * @param int    $capacity The amount of tokens.
     * @param string $unit     The unit for the amount, default is bytes.
     *
     * @throws \InvalidArgumentException The unit was invalid.
     */
    public function setCapacity($capacity, $unit = self::BYTES)
    {
        $this->capacity = $this->convertToBytes($capacity, $unit);
    }
    
    /**
     * Builds the Token Bucket.
     *
     * If no capacity was set, the capacity is set to the amount of tokens which
     * will be produced in one second.
     *
     * @return TokenBucket The Token Bucket
     */
    public function build()
    {
        $capacity = $this->capacity;
        if (empty($capacity)) {
            $capacity = ceil(TokenBucket::SECOND / $this->microRate);

        }
        return new TokenBucket($capacity, $this->microRate, $this->initialTokens);
    }
    
    /**
     * Converts an amount of an unit into the amount of bytes.
     *
     * @param int    $amount The amount.
     * @param string $unit   The unit for the amount.
     *
     * @return int The amount of bytes.
     * @throws \InvalidArgumentException The unit was invalid.
     */
    private function convertToBytes($amount, $unit)
    {
        if (!array_key_exists($unit, self::$unitMap)) {
            throw new \InvalidArgumentException(
                "Unit '$unit' should be one of TokenBucketBuilder's constants."
            );
        }
        return $amount * self::$unitMap[$unit];
    }
    
    /**
     * Set the rate in bytes per second.
     *
     * @param int $bytes Bytes per second rate.
     *
     * @deprecated since 0.2
     * @see TokenBucketBuilder::setRate()
     */
    public function setRateInBytesPerSecond($bytes)
    {
        $this->setRate($bytes);
    }
    
    /**
     * Set the rate in kibibytes per second.
     *
     * @param int $kibibytes Kibibytes per second rate.
     *
     * @deprecated since 0.2
     * @see TokenBucketBuilder::setRate()
     */
    public function setRateInKiBperSecond($kibibytes)
    {
        $this->setRate($kibibytes, self::KIBIBYTES);
    }
    
    /**
     * Set the rate in mebibytes per second.
     *
     * @param int $mebibytes mebibytes per second rate.
     *
     * @deprecated since 0.2
     * @see TokenBucketBuilder::setRate()
     */
    public function setRateInMiBPerSecond($mebibytes)
    {
        $this->setRate($mebibytes, self::MEBIBYTES);
    }
    
    /**
     * Sets the capacity in bytes.
     *
     * @param int $bytes The capacity in bytes.
     *
     * @deprecated since 0.2
     * @see TokenBucketBuilder::setCapacity()
     */
    public function setCapacityInBytes($bytes)
    {
        $this->setCapacity($bytes);
    }
    
    /**
     * Sets the capacity in kibibytes.
     *
     * @param int $kibibytes The capacity in kibibytes.
     *
     * @deprecated since 0.2
     * @see TokenBucketBuilder::setCapacity()
     */
    public function setCapacityInKiB($kibibytes)
    {
        $this->setCapacity($kibibytes, self::KIBIBYTES);
    }
    
    /**
     * Sets the capacity in mebibytes.
     *
     * @param int $mebibytes The capacity in mebibytes.
     *
     * @deprecated since 0.2
     * @see TokenBucketBuilder::setCapacity()
     */
    public function setCapacityInMiB($mebibytes)
    {
        $this->setCapacity($mebibytes, self::MEBIBYTES);
    }
}
