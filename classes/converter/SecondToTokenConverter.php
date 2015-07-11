<?php

namespace bandwidthThrottle\tokenBucket\converter;

use bandwidthThrottle\tokenBucket\Rate;

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
     * @var Rate The rate.
     */
    private $rate;
    
    /**
     * Sets the token rate.
     *
     * @param int $rate The rate.
     */
    public function __construct(Rate $rate)
    {
        $this->rate = $rate;
    }
    
    /**
     * Converts a duration of seconds into an amount of tokens.
     *
     * @param double $seconds The duration in seconds.
     * @return int The amount of tokens.
     */
    public function convert($seconds)
    {
        return (int) ($seconds * $this->rate->getTokensPerSecond());
    }
}
