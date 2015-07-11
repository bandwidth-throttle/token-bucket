<?php

namespace bandwidthThrottle\tokenBucket\storage\scope;

/**
 * Marker interface for the global scope.
 *
 * The global scope is shared amongst all processes.
 *
 * A Token bucket which uses a storage of the global scope can limit a rate
 * for a global resource. E.g. limit the aggregated bandwidth of a stream for
 * all processes.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
interface GlobalScope
{

}
