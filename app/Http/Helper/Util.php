<?php
namespace App\Http\Helper;

class Util
{
    /**
     * 以$field字段为key的三维数组
     *
     * @param array $data
     * @param string $field
     * @return array
     */
    public static function arraySplit(array $data, string $field)
    {
        $result = [];
        foreach ($data as $value) {
            $result[$value[$field]][] = $value;
        }
        return $result;
    }

    /**
     * 返回以$field字段为key的三维数组
     *
     * $array 是二维数组
     *
     * @param array $array
     * @param string $field
     * @return array
     */
    public static function arrayGroupBy(array $array, string $field)
    {
        return self::arraySplit($array, $field);
    }

    /**
     * 返回以$field字段为key的二维数组
     *
     * $array 是二维数组
     *
     * @param array $array
     * @param string $field
     * @return array
     */
    public static function arrayKeyBy(array $array, string $field)
    {
        $result = [];
        foreach ($array as $value) {
            $result[$value[$field]] = $value;
        }
        return $result;
    }

}
