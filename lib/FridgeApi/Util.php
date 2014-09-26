<?php
namespace FridgeApi;

class Util
{
    public static function is_assoc_array($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
