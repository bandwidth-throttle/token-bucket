<?php

namespace bandwidthThrottle\tokenBucket\converter;

/**
 * Tests for StringToDoubleConverter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see StringToDoubleConverter
 */
class StringToDoubleConverterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests convert() fails.
     *
     * @param string $input The input string.
     *
     * @test
     * @dataProvider provideTestConvertFails
     * @expectedException \bandwidthThrottle\tokenBucket\storage\StorageException
     */
    public function testConvertFails($input)
    {
        $converter = new StringToDoubleConverter();
        $converter->convert($input);
    }
    
    /**
     * Provides test cases for testConvertFails().
     *
     * @return array Test cases.
     */
    public function provideTestConvertFails()
    {
        return [
            [""],
            ["1234567"],
            ["123456789"],
        ];
    }

    /**
     * Tests convert().
     *
     * @param double $expected The expected double.
     * @param string $input    The input string.
     *
     * @test
     * @dataProvider provideTestConvert
     */
    public function testConvert($expected, $input)
    {
        $converter = new StringToDoubleConverter();
        $this->assertEquals($expected, $converter->convert($input));
    }
    
    /**
     * Provides test cases for testConvert().
     *
     * @return array Test cases.
     */
    public function provideTestConvert()
    {
        return [
            [0,   pack("d", 0)],
            [0.1, pack("d", 0.1)],
            [1,   pack("d", 1)],
            [1.1, pack("d", 1.1)],
        ];
    }
}
