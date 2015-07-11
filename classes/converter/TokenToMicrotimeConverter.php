<?php

namespace bandwidthThrottle\tokenBucket\converter;

/**
 * Token to microtime converter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @internal
 */
class TokenToMicrotimeConverter
{
    
    /**
     * @var TokenToSecondConverter Token converter.
     */
    private $secondsConverter;
    
    /**
     * Sets the token token converter.
     *
     * @param TokenToSecondConverter $secondsConverter Converter.
     */
    public function __construct(TokenToSecondConverter $secondsConverter)
    {
        $this->secondsConverter = $secondsConverter;
    }
    
    /**
     * Converts an amount of tokens into a timestamp.
     *
     * @param int $tokens The amount of tokens.
     * @return double The timestamp.
     */
    public function convert($tokens)
    {
        return microtime(true) - $this->secondsConverter->convert($tokens);
    }
}
