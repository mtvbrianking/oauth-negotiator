<?php

namespace Bmatovu\OAuthNegotiator\Tests\Support;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    /**
     * @test
     */
    public function can_determine_associative_array()
    {
        // Sequential arrays
        $arr_1 = array('a', 'b', 'c');
        $arr_2 = array(0 => 'a', 1 => 'b', 2 => 'c');
        $arr_3 = array('0' => 'a', '1' => 'b', '2' => 'c');

        // Associative arrays
        $arr_4 = array(1 => 'a', 0 => 'b', 2 => 'c');
        $arr_5 = array('1' => 'a', '0' => 'b', '2' => 'c');
        $arr_6 = array('a' => 'a', 'b' => 'b', 'c' => 'c');

        $this->assertFalse(is_associative($arr_1));
        $this->assertFalse(is_associative($arr_2));
        $this->assertFalse(is_associative($arr_3));

        $this->assertTrue(is_associative($arr_4));
        $this->assertTrue(is_associative($arr_5));
        $this->assertTrue(is_associative($arr_6));
    }

    /**
     * @test
     */
    public function can_get_missing_keys()
    {
        $required = ['a', 'b', 'c'];

        $given = ['c', 'd', 'e'];
        $missing = missing_keys($required, $given);
        $this->assertEquals($missing, ['a', 'b']);

        $given = ['c' => 3, 'd' => 4, 'e' => 5];
        $missing = missing_keys($required, $given);
        $this->assertEquals($missing, ['a', 'b']);
    }
}