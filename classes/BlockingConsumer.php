<?php

namespace bandwidthThrottle\tokenBucket;

/**
 * Blocking token bucket consumer.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class BlockingConsumer
{
    
    /**
     * @var TokenBucket The tocken bucket.
     */
    private $bucket;

    /**
     * Set the token bucket.
     * 
     * @param TokenBucket $bucket The token bucket.
     */
    public function __construct(TokenBucket $bucket)
    {
        $this->bucket = $bucket;
    }
    
    /**
     * Consumes tokens.
     *
     * If the underlying token bucket doesn't have sufficient tokens, the
     * consumer blocks until it can consume the tokens.
     *
     * @param int $tokens The token amount.
     * @throws \LengthException The token amount is larger than the bucket's capacity.
     */
    public function consume($tokens)
    {
        while (!$this->bucket->consume($tokens, $missingTokens)) {
            // sleep at least 1 millisecond.
            usleep(max(10000, $this->bucket->getMicroRate() * $missingTokens));
        }
    }
}
