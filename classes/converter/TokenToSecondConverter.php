<?php

namespace bandwidthThrottle\tokenBucket\converter;

use bandwidthThrottle\tokenBucket\Rate;

/**
 * Token to seconds converter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @internal
 */
class TokenToSecondConverter
{
    
    /**
     * @var Rate $rate The rate
     */
    private $rate;
    
    /**
     * Sets the token rate.
     *
     * @param Rate $rate The rate.
     */
    public function __construct(Rate $rate)
    {
        $this->rate = $rate;
    }
    
    /**
     * Converts an amount of tokens into a duration of seconds.
     *
     * @param int $tokens The amount of tokens.
     * @return double The seconds.
     */
    public function convert($tokens)
    {
        return $tokens / $this->rate->getTokensPerSecond();
    }
}
