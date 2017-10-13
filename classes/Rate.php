<?php

namespace bandwidthThrottle\tokenBucket;

/**
 * The rate.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
final class Rate
{
    
    const MICROSECOND = "microsecond";
    const MILLISECOND = "millisecond";
    const SECOND = "second";
    const MINUTE = "minute";
    const HOUR   = "hour";
    const DAY    = "day";
    const WEEK   = "week";
    const MONTH  = "month";
    const YEAR   = "year";

    /**
     * @var double[] Mapping between units and seconds
     */
    private static $unitMap = [
        self::MICROSECOND =>        0.000001,
        self::MILLISECOND =>        0.001,
        self::SECOND      =>        1,
        self::MINUTE      =>       60,
        self::HOUR        =>     3600,
        self::DAY         =>    86400,
        self::WEEK        =>   604800,
        self::MONTH       =>  2629743.83,
        self::YEAR        => 31556926,
    ];
    
    /**
     * @var int The amount of tokens to produce for the unit.
     */
    private $tokens;

    /**
     * @var string The unit.
     */
    private $unit;
    
    /**
     * Sets the amount of tokens which will be produced per unit.
     *
     * E.g. new Rate(100, Rate::SECOND) will produce 100 tokens per second.
     *
     * @param int    $tokens positive amount of tokens to produce per unit
     * @param string $unit   unit as one of Rate's constants
     */
    public function __construct($tokens, $unit)
    {
        if (!isset(self::$unitMap[$unit])) {
            throw new \InvalidArgumentException("Not a valid unit.");
        }
        if ($tokens <= 0) {
            throw new \InvalidArgumentException("Amount of tokens should be greater then 0.");
        }
        $this->tokens = $tokens;
        $this->unit   = $unit;
    }

    /**
     * Returns the rate in Tokens per second.
     *
     * @return double The rate.
     * @internal
     */
    public function getTokensPerSecond()
    {
        return $this->tokens / self::$unitMap[$this->unit];
    }
}
