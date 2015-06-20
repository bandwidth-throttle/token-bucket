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
     * One second in microseconds.
     * @internal
     */
    const SECOND = 1000000;
    
    /**
     * Initializes the Token bucket.
     *
     * @param int $capacity      Capacity of the bucket.
     * @param int $microRate     Microseconds for adding one token.
     * @param int $initialTokens Initial amount of tokens, default is 0.
     */
    public function __construct($capacity, $microRate, $initialTokens = 0)
    {
        $this->capacity  = $capacity;
        $this->microRate = $microRate;
        
        if ($initialTokens > $capacity) {
            throw new \LengthException(
                "Initial token amount ($initialTokens) is larger than the capacity ($capacity)."
            );
        }
        $this->setTokens($initialTokens);
    }
    
    /**
     * Consumes tokens for the packet.
     *
     * This method consumes only tokens if there are sufficient tokens available.
     * If there aren't sufficient tokens, no tokens will be removed and the
     * remaining amount of tokens is written to $missingTokens.
     *
     * @param int $tokens         The token amount.
     * @param int &$missingTokens The remaining amount of tokens to wait.
     *
     * @throws \LengthException The token amount is larger than the capacity.
     * @return bool If tokens were consumed. 
     */
    public function consume($tokens, &$missingTokens = 0)
    {
        if ($tokens > $this->capacity) {
            throw new \LengthException("Token amount ($tokens) is larger than the capacity ($this->capacity).");
        }
        
        // Drop overflowing tokens
        if ($this->getTokens() > $this->capacity) {
            $this->setTokens($this->capacity);
        }
        
        $delta = $this->getTokens() - $tokens;
        if ($delta < 0) {
            $missingTokens = -$delta;
            return false;

        } else {
            $this->removeTokens($tokens);
            $missingTokens = 0;
            return true;
        }
    }

    /**
     * Returns the amount of microseconds to produce one token.
     *
     * @return int Microseconds for one token.
     */
    public function getMicroRate()
    {
        return $this->microRate;
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
