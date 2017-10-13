<?php

namespace bandwidthThrottle\tokenBucket;

use bandwidthThrottle\tokenBucket\storage\StorageException;

/**
 * Blocking token bucket consumer.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
final class BlockingConsumer
{
    
    /**
     * @var TokenBucket The token bucket.
     */
    private $bucket;
    
    /**
     * @var int|null optional timeout in seconds.
     */
    private $timeout;

    /**
     * Set the token bucket and an optional timeout.
     *
     * @param TokenBucket $bucket The token bucket.
     * @param int|null $timeout Optional timeout in seconds.
     */
    public function __construct(TokenBucket $bucket, $timeout = null)
    {
        $this->bucket = $bucket;

        if ($timeout < 0) {
            throw new \InvalidArgumentException("Timeout must be null or positive");
        }
        $this->timeout = $timeout;
    }
    
    /**
     * Consumes tokens.
     *
     * If the underlying token bucket doesn't have sufficient tokens, the
     * consumer blocks until it can consume the tokens.
     *
     * @param int $tokens The token amount.
     *
     * @throws \LengthException The token amount is larger than the bucket's capacity.
     * @throws StorageException The stored microtime could not be accessed.
     * @throws TimeoutException The timeout was exceeded.
     */
    public function consume($tokens)
    {
        $timedOut = is_null($this->timeout) ? null : (microtime(true) + $this->timeout);
        while (!$this->bucket->consume($tokens, $seconds)) {
            self::throwTimeoutIfExceeded($timedOut);
            $seconds = self::keepSecondsWithinTimeout($seconds, $timedOut);
            
            // avoid an overflow before converting $seconds into microseconds.
            if ($seconds > 1) {
                // leave more than one second to avoid sleeping the minimum of one millisecond.
                $sleepSeconds = ((int) $seconds) - 1;

                sleep($sleepSeconds);
                $seconds -= $sleepSeconds;
            }

            // sleep at least 1 millisecond.
            usleep(max(1000, $seconds * 1000000));
        }
    }
    
    /**
     * Checks if the timeout was exceeded.
     *
     * @param float|null $timedOut Timestamp when to time out.
     * @throws TimeoutException The timeout was exceeded.
     */
    private static function throwTimeoutIfExceeded($timedOut)
    {
        if (is_null($timedOut)) {
            return;
        }
        if (time() >= $timedOut) {
            throw new TimeoutException("Timed out");
        }
    }
    
    /**
     * Adjusts the wait seconds to be within the timeout.
     *
     * @param float $seconds Seconds to wait for the next consume try.
     * @param float|null $timedOut Timestamp when to time out.
     *
     * @return float Seconds for waiting
     */
    private static function keepSecondsWithinTimeout($seconds, $timedOut)
    {
        if (is_null($timedOut)) {
            return $seconds;
        }
        $remainingSeconds = max($timedOut - microtime(true), 0);
        return min($remainingSeconds, $seconds);
    }
}
