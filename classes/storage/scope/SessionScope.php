<?php

namespace bandwidthThrottle\tokenBucket\storage\scope;

/**
 * Marker interface for the session scope.
 *
 * The session scope is available per session (i.e. per user).
 *
 * A Token bucket which uses a storage of the session scope can limit a rate
 * for a resource within a session. E.g. limit an API usage per user.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
interface SessionScope
{

}
