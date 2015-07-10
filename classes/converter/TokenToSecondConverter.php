<?php

namespace bandwidthThrottle\tokenBucket\converter;

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
     * Converts an amount of tokens into a duration of seconds.
     *
     * @param int $tokens The amount of tokens.
     * @return double The seconds.
     */
    public function convert($tokens)
    {
        return $tokens * $this->microRate / 1000000;
    }
}
