<?php

namespace bandwidthThrottle\tokenBucket;

use bandwidthThrottle\tokenBucket\storage\Storage;
use bandwidthThrottle\tokenBucket\storage\StorageException;
use bandwidthThrottle\tokenBucket\converter\TokenToMicrotimeConverter;
use bandwidthThrottle\tokenBucket\converter\TokenToSecondConverter;
use bandwidthThrottle\tokenBucket\converter\SecondToTokenConverter;

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
     * @var int precision scale for bc_* operations.
     */
    private $bcScale = 8;
    
    /**
     * @var Storage The storage.
     */
    private $storage;
    
    /**
     * @var TokenToSecondConverter Token to second converter.
     */
    private $tokenToSecondConverter;
    
    /**
     * @var TokenToMicrotimeConverter Token to microtime converter.
     */
    private $tokenToMicrotimeConverter;

    /**
     * @var SecondToTokenConverter Seconds to tokens converter.
     */
    private $secondToTokenConverter;
    
    /**
     * Initializes the Token bucket.
     *
     * @param int     $capacity  Capacity of the bucket.
     * @param int     $microRate Microseconds for adding one token.
     * @param Storage $storage   The storage.
     */
    public function __construct($capacity, $microRate, Storage $storage)
    {
        $this->capacity  = $capacity;
        $this->microRate = $microRate;
        $this->storage   = $storage;

        $this->tokenToSecondConverter    = new TokenToSecondConverter($microRate);
        $this->secondToTokenConverter    = new SecondToTokenConverter($microRate);
        $this->tokenToMicrotimeConverter = new TokenToMicrotimeConverter($this->tokenToSecondConverter);
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
     * @param int $initialTokens Initial amount of tokens, default is 0.
     *
     * @throws StorageException Bootstrapping failed.
     * @throws \LengthException The initial amount of tokens is larger than the capacity.
     */
    public function bootstrap($initialTokens = 0)
    {
        if ($initialTokens > $this->capacity) {
            throw new \LengthException(
                "Initial token amount ($initialTokens) is larger than the capacity ($this->capacity)."
            );
        }
        
        $this->storage->getMutex()
            ->check(function () {
                return !$this->storage->isBootstrapped();
            })
            ->then(function () use ($initialTokens) {
                $this->storage->bootstrap($this->tokenToMicrotimeConverter->convert($initialTokens));
            });
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
     * @return bool If tokens were consumed.
     * @SuppressWarnings(PHPMD)
     *
     * @throws \LengthException The token amount is larger than the capacity.
     * @throws StorageException The stored microtime could not be accessed.
     */
    public function consume($tokens, &$missingTokens = 0)
    {
        if ($tokens > $this->capacity) {
            throw new \LengthException("Token amount ($tokens) is larger than the capacity ($this->capacity).");
        }
        
        return $this->storage->getMutex()->synchronized(
            function () use ($tokens, &$missingTokens) {

                $microtime = $this->storage->getMicrotime();
            
                // Drop overflowing tokens
                $minMicrotime = $this->tokenToMicrotimeConverter->convert($this->capacity);
                if ($minMicrotime > $microtime) {
                    $microtime = $minMicrotime;
                }

                $delta = $this->getTokens($microtime) - $tokens;
                if ($delta < 0) {
                    $missingTokens = -$delta;
                    return false;

                } else {
                    $microtime += $this->tokenToSecondConverter->convert($tokens);
                    $this->storage->setMicrotime($microtime);
                    $missingTokens = 0;
                    return true;
                }
            }
        );
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
     * @param double $microtime The timestamp.
     *
     * @return int The tokens.
     */
    private function getTokens($microtime)
    {
        $delta = bcsub(microtime(true), $microtime, $this->bcScale);
        return $this->secondToTokenConverter->convert($delta);
    }
}
