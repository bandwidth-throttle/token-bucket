<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;

/**
 * Memcached based storage which can be shared among processes.
 *
 * This storage is in the global scope.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class MemcachedStorage implements Storage, GlobalScope
{

}
