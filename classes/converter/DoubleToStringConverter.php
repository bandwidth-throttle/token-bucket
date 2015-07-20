<?php

namespace bandwidthThrottle\tokenBucket\converter;

/**
 * Double to string converter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @internal
 */
class DoubleToStringConverter
{
    
    /**
     * Converts a 64 bit double to an 8 byte string.
     *
     * @param double $double The 64 bit double.
     * @return string The 8 byte string representation.
     */
    public function convert($double)
    {
        $string = pack("d", $double);
        assert(8 === strlen($string));
        return $string;
    }
}
