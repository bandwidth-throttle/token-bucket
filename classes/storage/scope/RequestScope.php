<?php

namespace bandwidthThrottle\tokenBucket\storage\scope;

/**
 * Marker interface for the request scope.
 *
 * The request scope is available only per process (i.e. per request).
 *
 * A Token bucket which uses a storage of the request scope can limit a rate
 * for a resource which is used within one request. E.g. bandwidth throtteling
 * for downloading a stream.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
interface RequestScope
{

}
