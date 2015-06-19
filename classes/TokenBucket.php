<?php

namespace bandwidthThrottle\tokenBucket;

/**
 * Token Bucket algorithm.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class TokenBucket
{

    /**
     * @var int Microseconds for adding one token.
     */
    private $microRate;
    
    /**
     * @var int Token capacity of this bucket.
     */
    private $capacity;
    
    /**
     * @var float last micro timestamp when tokens where added.
     */
    private $microTimestamp;
    
    /**
     * @var int precision scale for bc_* operations.
     */
    private $bcScale = 8;
    
    /**
     * One millisecond in microseconds.
     * @internal
     */
    const MILLISECOND = 1000;
    
    /**
     * One secon in microseconds.
     * @internal
     */
    const SECOND = 1000000;
    
    /**
     * Initializes an empty Token bucket.
     *
     * @param int $capacity  Capacity of the bucket.
     * @param int $microRate Microseconds for adding one token.
     */
    public function __construct($capacity, $microRate)
    {
        $this->capacity       = $capacity;
        $this->microRate      = $microRate;
        $this->microTimestamp = microtime(true);
    }
    
    /**
     * Consumes tokens for the packet.
     *
     * Consumes tokens for the packet size. If there aren't sufficient tokens
     * the method blocks until there are enough tokens.
     *
     * @param int $tokens The token amount.
     * @throws \LengthException The token amount is larger than the capacity.
     */
    public function consume($tokens)
    {
        if ($tokens > $this->capacity) {
            throw new \LengthException("Token amount ($tokens) is larger than the capacity ($this->capacity).");
        }
        
        // Drop overflowing tokens
        if ($this->getTokens() > $this->capacity) {
            $this->setTokens($this->capacity);
        }
        
        // Wait until tokens are refilled
        while ($this->getTokens() < $tokens) {
            $missingTokens = $tokens - $this->getTokens();
            if ($missingTokens <= 0) {
                break;

            }
            // sleep, but not less than a millisecond
            usleep(max($missingTokens * $this->microRate, self::MILLISECOND));
        }
        
        $this->removeTokens($tokens);
    }
        
    /**
     * The token capacity of this bucket.
     *
     * @return int The capacity.
     */
    public function getCapacity()
    {
        return $this->capacity;
    }
    
    /**
     * Returns the tokens.
     *
     * @return int The tokens.
     */
    private function getTokens()
    {
        $delta = bcsub(microtime(true), $this->microTimestamp, $this->bcScale);
        return $this->convertSecondsToTokens($delta);
    }
    
    /**
     * Sets the amount of tokens of this bucket.
     *
     * @param int $tokens The amount of tokens.
     */
    private function setTokens($tokens)
    {
        $delta = $this->convertTokensToSeconds($tokens);
        $this->microTimestamp = microtime(true) - $delta;
    }
    
    /**
     * Removes token from this bucket.
     *
     * @param int $tokens The amount of tokens.
     */
    private function removeTokens($tokens)
    {
        $delta = $this->convertTokensToSeconds($tokens);
        $this->microTimestamp += $delta;
    }
    
    /**
     * Converts a duration of seconds into an amount of tokens.
     *
     * @param float The duration in seconds.
     *
     * @return int The amount of tokens.
     */
    private function convertSecondsToTokens($seconds)
    {
        return (int) ($seconds * self::SECOND / $this->microRate);
    }
    
    /**
     * Converts an amount of tokens into a duration of seconds.
     *
     * @param int $tokens The amount of tokens.
     *
     * @return float The duration in seconds.
     */
    private function convertTokensToSeconds($tokens)
    {
        return $tokens * $this->microRate / self::SECOND;
    }
}
