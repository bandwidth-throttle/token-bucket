<?php

namespace bandwidthThrottle\tokenBucket\converter;

use bandwidthThrottle\tokenBucket\storage\StorageException;

/**
 * String to double converter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @internal
 */
class StringToDoubleConverter
{
    
    /**
     * Converts an 8 byte string to a 64 bit double.
     *
     *
     * @param string $string The 8 byte string representation.
     * @return double The 64 bit double.
     * @throws StorageException Conversion error.
     */
    public function convert($string)
    {
        if (strlen($string) !== 8) {
            throw new StorageException("The string is not 64 bit long.");

        }
        $unpack = unpack("d", $string);
        if (!is_array($unpack) || !array_key_exists(1, $unpack)) {
            throw new StorageException("Could not unpack string.");

        }
        return $unpack[1];
    }
}
