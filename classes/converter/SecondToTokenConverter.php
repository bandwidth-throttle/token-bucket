<?php

namespace bandwidthThrottle\tokenBucket\converter;

/**
 * Seconds to tokens converter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @internal
 */
class SecondToTokenConverter
{
    
    /**
     * @var int Microseconds for adding one token.
     */
    private $microRate;
    
    /**
     * Sets the token rate.
     *
     * @param int $microRate Microseconds for adding one token.
     */
    public function __construct($microRate)
    {
        $this->microRate = $microRate;
    }
    
    /**
     * Converts a duration of seconds into an amount of tokens.
     *
     * @param double $seconds The duration in seconds.
     * @return int The amount of tokens.
     */
    public function convert($seconds)
    {
        return (int) ($seconds * 1000000 / $this->microRate);
    }
}
