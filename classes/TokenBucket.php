<?php

namespace bandwidthThrottle\tokenBucket;

use malkusch\lock\exception\MutexException;
use bandwidthThrottle\tokenBucket\storage\Storage;
use bandwidthThrottle\tokenBucket\storage\StorageException;
use bandwidthThrottle\tokenBucket\util\TokenConverter;

/**
 * Token Bucket algorithm.
 *
 * The token bucket algorithm can be used for controlling the usage rate
 * of a resource. The scope of that rate is determined by the underlying
 * storage.
 *
 * Example:
 * <code>
 * use bandwidthThrottle\tokenBucket\Rate;
 * use bandwidthThrottle\tokenBucket\TokenBucket;
 * use bandwidthThrottle\tokenBucket\storage\FileStorage;
 *
 * $storage = new FileStorage(__DIR__ . "/api.bucket");
 * $rate    = new Rate(10, Rate::SECOND);
 * $bucket  = new TokenBucket(10, $rate, $storage);
 * $bucket->bootstrap(10);
 *
 * if (!$bucket->consume(1, $seconds)) {
 *     http_response_code(429);
 *     header(sprintf("Retry-After: %d", floor($seconds)));
 *     exit();
 * }
 * </code>
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
final class TokenBucket
{

    /**
     * @var Rate The rate.
     */
    private $rate;
    
    /**
     * @var int Token capacity of this bucket.
     */
    private $capacity;
    
    /**
     * @var Storage The storage.
     */
    private $storage;
    
    /**
     * @var TokenConverter Token converter.
     */
    private $tokenConverter;
    
    /**
     * Initializes the Token bucket.
     *
     * The storage determines the scope of the bucket.
     *
     * @param int     $capacity  positive capacity of the bucket
     * @param Rate    $rate      rate
     * @param Storage $storage   storage
     */
    public function __construct($capacity, Rate $rate, Storage $storage)
    {
        if ($capacity <= 0) {
            throw new \InvalidArgumentException("Capacity should be greater than 0.");
        }

        $this->capacity = $capacity;
        $this->rate     = $rate;
        $this->storage  = $storage;

        $this->tokenConverter = new TokenConverter($rate);
    }
    
    /**
     * Bootstraps the storage with an initial amount of tokens.
     *
     * If the storage was already bootstrapped this method returns silently.
     *
     * While you could call bootstrap() on each request, you should not do that!
     * This method will do unnecessary storage communications just to see that
     * bootstrapping was performed already. You therefore should call that
     * method in your application's bootstrap or deploy process.
     *
     * This method is threadsafe.
     *
     * @param int $tokens Initial amount of tokens, default is 0.
     *
     * @throws StorageException Bootstrapping failed.
     * @throws \LengthException The initial amount of tokens is larger than the capacity.
     */
    public function bootstrap($tokens = 0)
    {
        try {
            if ($tokens > $this->capacity) {
                throw new \LengthException(
                    "Initial token amount ($tokens) is larger than the capacity ($this->capacity)."
                );
            }
            if ($tokens < 0) {
                throw new \InvalidArgumentException(
                    "Initial token amount ($tokens) should be greater than 0."
                );
            }
            
            $this->storage->getMutex()
                ->check(function () {
                    return !$this->storage->isBootstrapped();
                })
                ->then(function () use ($tokens) {
                    $this->storage->bootstrap($this->tokenConverter->convertTokensToMicrotime($tokens));
                });
        } catch (MutexException $e) {
            throw new StorageException("Could not lock bootstrapping", 0, $e);
        }
    }
    
    /**
     * Consumes tokens from the bucket.
     *
     * This method consumes only tokens if there are sufficient tokens available.
     * If there aren't sufficient tokens, no tokens will be removed and the
     * remaining seconds to wait are written to $seconds.
     *
     * This method is threadsafe.
     *
     * @param int    $tokens   The token amount.
     * @param double &$seconds The seconds to wait.
     *
     * @return bool If tokens were consumed.
     * @SuppressWarnings(PHPMD)
     *
     * @throws \LengthException The token amount is larger than the capacity.
     * @throws StorageException The stored microtime could not be accessed.
     */
    public function consume($tokens, &$seconds = 0)
    {
        try {
            if ($tokens > $this->capacity) {
                throw new \LengthException("Token amount ($tokens) is larger than the capacity ($this->capacity).");
            }
            if ($tokens <= 0) {
                throw new \InvalidArgumentException("Token amount ($tokens) should be greater than 0.");
            }

            return $this->storage->getMutex()->synchronized(
                function () use ($tokens, &$seconds) {
                    $tokensAndMicrotime = $this->loadTokensAndTimestamp();
                    $microtime = $tokensAndMicrotime["microtime"];
                    $availableTokens = $tokensAndMicrotime["tokens"];

                    $delta = $availableTokens - $tokens;
                    if ($delta < 0) {
                        $this->storage->letMicrotimeUnchanged();
                        $passed  = microtime(true) - $microtime;
                        $seconds = max(0, $this->tokenConverter->convertTokensToSeconds($tokens) - $passed);
                        return false;
                    } else {
                        $microtime += $this->tokenConverter->convertTokensToSeconds($tokens);
                        $this->storage->setMicrotime($microtime);
                        $seconds = 0;
                        return true;
                    }
                }
            );
        } catch (MutexException $e) {
            throw new StorageException("Could not lock token consumption.", 0, $e);
        }
    }

    /**
     * Returns the token add rate.
     *
     * @return Rate The rate.
     */
    public function getRate()
    {
        return $this->rate;
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
     * Returns the currently available tokens of this bucket.
     *
     * This is a purely informative method. Use this method if you are
     * interested in the amount of remaining tokens. Those tokens
     * could be consumed instantly. This method will not consume any token.
     * Use {@link consume()} to do so.
     *
     * This method will never return more than the capacity of the bucket.
     *
     * @return int amount of currently available tokens
     * @throws StorageException The stored microtime could not be accessed.
     */
    public function getTokens()
    {
        return $this->loadTokensAndTimestamp()["tokens"];
    }
    
    /**
     * Loads the stored timestamp and its respective amount of tokens.
     *
     * This method is a convenience method to allow sharing code in
     * {@link TokenBucket::getTokens()} and {@link TokenBucket::consume()}
     * while accessing the storage only once.
     *
     * @throws StorageException The stored microtime could not be accessed.
     * @return array tokens and microtime
     */
    private function loadTokensAndTimestamp()
    {
        $microtime = $this->storage->getMicrotime();
        
        // Drop overflowing tokens
        $minMicrotime = $this->tokenConverter->convertTokensToMicrotime($this->capacity);
        if ($minMicrotime > $microtime) {
            $microtime = $minMicrotime;
        }
        
        $tokens = $this->tokenConverter->convertMicrotimeToTokens($microtime);
        return [
            "tokens" => $tokens,
            "microtime" => $microtime
        ];
    }
}
