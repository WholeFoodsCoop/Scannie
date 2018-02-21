<?php
class FormLib 
{
    public static function get($name, $default='')
    {
        return self::getFormValue($name, $default);
    }

    public static function getFormValue($name, $default='')
    {
        $val = filter_input(INPUT_GET, $name, FILTER_CALLBACK, array('options'=>array('FormLib','filterCallback')));
        if ($val === null) {
            $val = filter_input(INPUT_POST, $name, FILTER_CALLBACK, array('options'=>array('FormLib','filterCallback')));
        }
        if ($val === null) {
            $val = $default;
        }
        return $val;
    }

    private static function filterCallback($item)
    {
        return $item;
    }
}
